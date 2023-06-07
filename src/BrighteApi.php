<?php

declare(strict_types=1);

namespace BrighteCapital\Api;

use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Auth0\SDK\Auth0;
use stdClass;

class BrighteApi
{
    public const ERROR_FIELD_NAME_IN_JSON = 'errors';
    public const JWT_SERVICE_CACHE_KEY = 'service_jwt';

    /** @var string|null */
    public $clientId;

    /** @var string|null */
    public $clientSecret;

    /** @var string scheme */
    protected $scheme;

    /** @var string host */
    protected $host;

    /** @var string prefix */
    protected $prefix;

    /** @var int port */
    protected $port;

    /**
     * @deprecated please don't use this anymore
     * @var string|null apiKey
     **/
    protected $apiKey;

    /** @var string accessToken */
    protected $accessToken;

    /** @var string[] accessTokens */
    protected $accessTokens = [];

    /** @var \Psr\Http\Client\ClientInterface HTTP client */
    protected $http;

    /** @var \Psr\Log\LoggerInterface Logger */
    protected $logger;

    /** @var (string|int|bool)[][] */
    protected $cache = [];

    /** @var CacheItemPoolInterface|null */
    protected $cacheItemPool;

    /** @var string jwtCacheKey */
    protected $jwtCacheKey;

    /** @var \Auth0\SDK\Auth0 auth0 */
    protected $auth0;

    /**
     * @param \Psr\Http\Client\ClientInterface $http HTTP client
     * @param \Psr\Log\LoggerInterface $log Logger
     * @param (string|int)[] $config configuration for API
     * @param CacheItemPoolInterface|null $cache
     * @Inject({"config"="settings.brighteApi"})
     */
    public function __construct(
        ClientInterface $http,
        LoggerInterface $log,
        array $config,
        ?CacheItemPoolInterface $cache
    ) {
        $uri = new Uri($config['uri']);
        $this->scheme = $uri->getScheme();
        $this->host = $uri->getHost();
        $this->prefix = $uri->getPath();
        $this->port = $uri->getPort();
        $this->clientId = $config['client_id'] ?? null;
        $this->clientSecret = $config['client_secret'] ?? null;
        $this->apiKey = $config['key'] ?? null;
        $this->http = $http;
        $this->logger = $log;
        $this->cacheItemPool = $cache;
        $this->jwtCacheKey = $this->clientId . '_' . self::JWT_SERVICE_CACHE_KEY;
        $this->auth0 = new Auth0([
            'domain' => $config['auth0_domain'],
            'clientId' => $this->clientId,
            'clientSecret' => $this->clientSecret,
        ]);
    }

    public function getToken(string $audience): string
    {
        $this->accessToken = $this->accessTokens[$audience] ?? null;
        if ($this->accessToken) {
            if (!self::isTokenExpired($this->accessToken)) {
                return $this->accessToken;
            }

            $this->cacheItemPool->deleteItem($this->jwtCacheKey . '_' . self::cleanAudience($audience));
        }

        $this->authenticate($audience);

        return $this->accessToken;
    }

    protected function authenticate(string $audience): void
    {

        if ($this->cacheItemPool && $accessToken = $this->cacheItemPool->getItem($this->jwtCacheKey . '_' . self::cleanAudience($audience))) {
            $this->accessTokens[$audience] = $accessToken->get();
            if ($this->accessTokens[$audience]) {
                $this->logger->debug("Fetched Service JWT from cache");
                return;
            }
        }

        $this->logger->info("Not authenticated with Brighte APIs, authenticating");
        if ($this->clientId && $this->clientSecret) {
            $response = $this->auth0->authentication()->clientCredentials([
                'audience' => $audience,
            ]);
        } else {
            $authPath = '/identity/authenticate';
            $authBody = json_encode(['apiKey' => $this->apiKey]);
            $response = $this->post($authPath, $authBody, '', [], null);
        }

        $body = json_decode((string) $response->getBody());

        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            throw new \InvalidArgumentException($body->message ?? $response->getReasonPhrase());
        }

        $this->accessToken = $body->access_token ?? $body->accessToken;

        if ($this->cacheItemPool) {
            $item = $this->cacheItemPool->getItem($this->jwtCacheKey . '_' . self::cleanAudience($audience));
            $item->set($this->accessToken);
            $expires = $body->expires_in ?? null;
            $expires = (int) $expires ?: new \DateInterval('PT' . strtoupper($expires ?: "15m"));
            $item->expiresAfter($expires);
            $this->cacheItemPool->save($item);
            $this->logger->info("Service JWT stored in cache");
        }
    }

    /**
     * @param string $path
     * @param string $query
     * @param string[] $headers
     * @param string|null $audience
     * @return \Psr\Http\Message\ResponseInterface
     **/
    public function get(
        string $path,
        string $query = '',
        array $headers = [],
        string $audience = null
    ): ResponseInterface {
        return $this->getCached(
            $path . '?' . $query,
            [$this, 'doRequest'],
            ['GET', $path, $query, null, $headers, $audience]
        );
    }

    /**
     * @param string $key cache key
     * @param callable $callback function to get fresh response
     * @param (string|string[])[] $args
     */
    protected function getCached(string $key, callable $callback, array $args): ResponseInterface
    {
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $response = ($callback(...$args));

        if ($response->getStatusCode() == StatusCodeInterface::STATUS_OK) {
            $this->cache[$key] = $response;
        }

        return $response;
    }

    /**
     * @param string $path
     * @param string $body
     * @param string $query
     * @param string[] $headers
     * @param string|null $audience
     * @return \Psr\Http\Message\ResponseInterface
     **/
    public function post(
        string $path,
        string $body,
        string $query = '',
        array $headers = [],
        string $audience = null
    ): ResponseInterface {
        return $this->doRequest('POST', $path, $query, $body, $headers, $audience);
    }

    public function cachedPost(
        string $functionName,
        array $parameters,
        string $path,
        string $body,
        string $query = '',
        array $headers = [],
        string $audience = null
    ) {
        $key = implode('_', [$functionName, implode('_', $parameters)]);
        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }
        if ($this->cacheItemPool && $this->cacheItemPool->hasItem($key)) {
            return $this->cacheItemPool->getItem($key)->get();
        }

        $response = $this->doRequest('POST', $path, $query, $body, $headers, $audience);

        $responseBody = $this->checkIfContainsError($functionName, $response);
        if ($responseBody === null) {
            return null;
        }

        $this->cache[$key] = $responseBody;
        if ($this->cacheItemPool) {
            $item = $this->cacheItemPool->getItem($key);
            $item->set($responseBody);
            $expires = new \DateInterval('PT' . strtoupper("15m"));
            $item->expiresAfter($expires);
            $this->cacheItemPool->save($item);
        }

        return $responseBody;
    }

    private function checkIfContainsError(string $function, ResponseInterface $response)
    {
        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            $this->logGraphqlResponse($function, $response);

            return null;
        }

        $json = $response->getBody()->getContents();
        $body = json_decode($json);

        if (property_exists($body, self::ERROR_FIELD_NAME_IN_JSON)) {
            $this->logGraphqlResponse($function, $response);

            return null;
        }
        return $body;
    }

    /**
     * @param string $method
     * @param string $path
     * @param string $query
     * @param string|null $body
     * @param string[] $headers
     * @param string|null $audience
     * @return \Psr\Http\Message\ResponseInterface
     **/
    protected function doRequest(
        string $method,
        string $path,
        string $query,
        ?string $body,
        array $headers,
        string $audience = null
    ): ResponseInterface {
        $this->logger->debug(
            'BrighteApi->' . __FUNCTION__,
            $path === '/identity/authenticate' ? compact('path') : func_get_args()
        );

        $headers = array_merge([
            'content-type' => 'application/json',
            'accept' => 'application/json',
        ], $headers);

        if ($audience) {
            $headers['Authorization'] = 'Bearer ' . $this->getToken($audience);
        }

        $path = UriResolver::removeDotSegments($this->prefix . $path);

        return  $this->http->sendRequest(new Request(
            $method,
            Uri::fromParts([
                'scheme' => $this->scheme,
                'host' => $this->host,
                'port' => $this->port,
                'path' => $path,
                'query' => $query,
            ]),
            $headers,
            $body
        ));
    }

    /**
     * @param string $token
     * @return bool
     */
    private static function isTokenExpired(string $token): bool
    {
        $bufferInSeconds = 3;

        $currentTimestamp = time();
        $decoded = self::decodeToken($token);

        return $currentTimestamp > ($decoded->exp - $bufferInSeconds);
    }

    /**
     * @param string $token
     * @return \stdClass
     */
    private static function decodeToken(string $token): \stdClass
    {
        $tokenPayload = explode(".", $token)[1];

        return json_decode(base64_decode($tokenPayload));
    }

    private function logGraphqlResponse(string $function, ResponseInterface $response): void
    {
        $body = json_decode((string) $response->getBody()) ?? new stdClass();
        $message = sprintf(
            '%s->%s: %d: %s',
            self::class,
            $function,
            $response->getStatusCode(),
            $body->errors[0]->message ?? $response->getReasonPhrase()
        );
        $this->logger->warning($message);
    }

    /**
     * Remove any invalid characters for cache key
     * message: key contains one or more characters reserved for future extension: {}()/\\@:
     * @param string $audience
     * @return string
     */
    private static function cleanAudience(string $audience): string
    {
        return str_replace(['https://', '/'], ['', '_'], $audience);
    }
}
