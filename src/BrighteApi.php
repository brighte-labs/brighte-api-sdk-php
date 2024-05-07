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
use stdClass;

class BrighteApi
{
    public const ERROR_FIELD_NAME_IN_JSON = 'errors';
    public const JWT_SERVICE_CACHE_KEY = 'service_jwt';
    public const AUTH0 = 'auth0';
    public const BRIGHTE_API = 'brighteApi';

    /** @var string|null */
    public $clientId;

    /** 
     * @var string|null
     * @deprecated for old identity service calls
     **/
    public $legacyClientId;

    /** @var string|null */
    public $clientSecret;

    /** @var string[] scheme */
    protected $scheme = [];

    /** @var string[] host */
    protected $host = [];

    /** @var string[] prefix */
    protected $prefix = [];

    /** @var int[] port */
    protected $port = [];

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

    /** @var string accessToken */
    protected $jwtCacheKey;

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
        $this->clientId = $config['client_id'] ?? null;
        $this->legacyClientId = $config['legacy_client_id'] ?? null;
        $this->clientSecret = $config['client_secret'] ?? null;
        $this->apiKey = $config['key'] ?? null;
        $this->http = $http;
        $this->logger = $log;
        $this->cacheItemPool = $cache;
        $this->jwtCacheKey = $this->clientId . '_' . self::JWT_SERVICE_CACHE_KEY;
        $this->setUri(self::BRIGHTE_API, $config['uri']);
        $this->setUri(self::AUTH0, 'https://' . $config['auth0_domain']);
    }

    public function getToken(string $audience): string
    {
        $this->accessToken = $this->accessTokens[$audience] ?? null;
        if ($this->accessToken) {
            if (!self::isTokenExpired($this->accessToken)) {
                return $this->accessToken;
            }

            $this->cacheItemPool->deleteItem($this->getCacheKey($audience));
        }

        $this->authenticate($audience);

        return $this->accessToken;
    }

    protected function authenticate(string $audience): void
    {

        if ($this->cacheItemPool && $accessToken = $this->cacheItemPool->getItem($this->getCacheKey($audience))) {
            $this->accessTokens[$audience] = $accessToken->get();
            if ($this->accessTokens[$audience]) {
                $this->logger->debug("Fetched Service JWT from cache");
                $this->accessToken = $this->accessTokens[$audience];
                return;
            }
        }

        $this->logger->info("Not authenticated with Brighte APIs, authenticating");
        if ($this->clientId && $this->clientSecret) {
            $authPath = '/oauth/token';
            $options = [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'client_credentials',
                'audience' => $audience,
            ];
            $authBody = \json_encode($options);
            $response = $this->post($authPath, $authBody, '', [], null, self::AUTH0);
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
            $item = $this->cacheItemPool->getItem($this->getCacheKey($audience));
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
     * @param string|null $audiencePath
     * @return \Psr\Http\Message\ResponseInterface
     **/
    public function get(
        string $path,
        string $query = '',
        array $headers = [],
        string $audiencePath = null
    ): ResponseInterface {
        $audience = $this->buildAudience($audiencePath);
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
     * @param string|null $audiencePath
     * @param string $service
     * @return \Psr\Http\Message\ResponseInterface
     **/
    public function post(
        string $path,
        string $body,
        string $query = '',
        array $headers = [],
        string $audiencePath = null,
        string $service = self::BRIGHTE_API
    ): ResponseInterface {
        $audience = $this->buildAudience($audiencePath);
        return $this->doRequest('POST', $path, $query, $body, $headers, $audience, $service);
    }

    public function cachedPost(
        string $functionName,
        array $parameters,
        string $path,
        string $body,
        string $query = '',
        array $headers = [],
        string $audiencePath = null,
        bool $debug = false
    ) {
        $key = implode('_', [$functionName, implode('_', $parameters)]);
        if (array_key_exists($key, $this->cache)) {
            if ($debug) {
                $this->logger->debug(print_r(__FUNCTION__ . ': cache key:' . $key . '| value:' . $this->cache[$key], true));
            }
            return $this->cache[$key];
        }
        if ($this->cacheItemPool && $this->cacheItemPool->hasItem($key)) {
            $value = $this->cacheItemPool->getItem($key)->get();
            if ($debug) {
                $this->logger->debug(print_r(__FUNCTION__ . ': cache-item-pool key:' . $key . '| value:' . $value, true));
            }
            return $value;
        }

        $audience = $this->buildAudience($audiencePath);
        $response = $this->doRequest('POST', $path, $query, $body, $headers, $audience);

        $responseBody = $this->checkIfContainsError($functionName, $response);
        if ($responseBody === null) {
            if ($debug) {
                $this->logger->debug(print_r(__FUNCTION__ . ': response is null. request query: ' . $query . '| body:' . $body, true));
            }
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
     * @param string $service
     * @return \Psr\Http\Message\ResponseInterface
     **/
    protected function doRequest(
        string $method,
        string $path,
        string $query,
        ?string $body,
        array $headers,
        string $audience = null,
        string $service = self::BRIGHTE_API
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

        $path = UriResolver::removeDotSegments($this->prefix[$service] . $path);

        return $this->http->sendRequest(
            new Request(
                $method,
                Uri::fromParts([
                    'scheme' => $this->scheme[$service],
                    'host' => $this->host[$service],
                    'port' => $this->port[$service],
                    'path' => $path,
                    'query' => $query,
                ]),
                $headers,
                $body
            )
        );
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
     * @param string|null $audiencePath
     * @return string|null
     */
    private function buildAudience($audiencePath): ?string
    {
        if ($audiencePath === null) {
            return null;
        }
        $path = UriResolver::removeDotSegments($this->prefix[self::BRIGHTE_API] . $audiencePath);
        return $this->scheme[self::BRIGHTE_API] . '://' . $this->host[self::BRIGHTE_API] . $path;
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

    /**
     * @param string $service
     * @param string $uri
     * @return void
     */
    private function setUri(string $service, string $uri): void
    {
        $uri = new Uri($uri);
        $this->scheme[$service] = $uri->getScheme();
        $this->host[$service] = $uri->getHost();
        $this->prefix[$service] = $uri->getPath();
        $this->port[$service] = $uri->getPort();
    }

    /**
     * Cache key
     * @param string $audience
     * @return string
     */
    private function getCacheKey(string $audience): string
    {
        return $this->jwtCacheKey . '_' . self::cleanAudience($audience);
    }
}
