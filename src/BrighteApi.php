<?php

declare(strict_types=1);

namespace BrighteCapital\Api;

use Cache\Adapter\Common\CacheItem;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class BrighteApi
{

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

    /** @var \Psr\Http\Client\ClientInterface HTTP client */
    protected $http;

    /** @var \Psr\Log\LoggerInterface Logger */
    protected $logger;

    /** @var (string|int|bool)[][] */
    protected $cache = [];

    /** @var CacheItemPoolInterface|null */
    protected $cacheItemPool;

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
    }

    protected function getToken(): string
    {
        if ($this->accessToken) {
            if (!$this->isTokenExpired($this->accessToken)) {
                return $this->accessToken;
            }

            $this->cacheItemPool->deleteItem('service_jwt');
        }

        $this->authenticate();

        return $this->accessToken;
    }

    protected function authenticate(): void
    {
        if ($this->cacheItemPool && $accessToken = $this->cacheItemPool->getItem('service_jwt')) {
            $this->accessToken = $accessToken->get();
            if ($this->accessToken) {
                $this->logger->debug("Fetched Service JWT from cache");
                return;
            }
        }

        $this->logger->info("Not authenticated with Brighte APIs, authenticating");
        if ($this->clientId && $this->clientSecret) {
            $authPath = '/identity/token';
            $options = [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'client_credentials',
            ];
            $authBody = \json_encode($options);
        } else {
            $authPath = '/identity/authenticate';
            $authBody = json_encode(['apiKey' => $this->apiKey]);
        }
        $response = $this->post($authPath, $authBody, '', [], false);
        $body = json_decode((string) $response->getBody());

        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            throw new \InvalidArgumentException($body->message ?? $response->getReasonPhrase());
        }

        $this->accessToken = $body->access_token ?? $body->accessToken;

        if ($this->cacheItemPool) {
            $item = new CacheItem('service_jwt', true, $this->accessToken);
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
     * @param bool $auth use authentication?
     * @return \Psr\Http\Message\ResponseInterface
     **/
    public function get(string $path, string $query = '', array $headers = [], bool $auth = true): ResponseInterface
    {
        return $this->getCached(
            $path . '?' . $query,
            [$this, 'doRequest'],
            ['GET', $path, $query, null, $headers, $auth]
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
     * @param bool $auth use authentication?
     * @return \Psr\Http\Message\ResponseInterface
     **/
    public function post(
        string $path,
        string $body,
        string $query = '',
        array $headers = [],
        bool $auth = true
    ): ResponseInterface {
        return $this->doRequest('POST', $path, $query, $body, $headers, $auth);
    }

    /**
     * @param string $method
     * @param string $path
     * @param string $query
     * @param string|null $body
     * @param string[] $headers
     * @param bool $auth use authentication?
     * @return \Psr\Http\Message\ResponseInterface
     **/
    protected function doRequest(
        string $method,
        string $path,
        string $query,
        ?string $body,
        array $headers,
        bool $auth = true
    ): ResponseInterface {
        $this->logger->debug(
            'BrighteApi->' . __FUNCTION__,
            $path === '/identity/authenticate' ? compact('path') : func_get_args()
        );

        $headers = array_merge([
            'content-type' => 'application/json',
            'accept' => 'application/json',
        ], $headers);

        if ($auth) {
            $headers['Authorization'] = 'Bearer ' . $this->getToken();
        }

        return  $this->http->sendRequest(new Request(
            $method,
            Uri::fromParts([
                'scheme' => $this->scheme,
                'host' => $this->host,
                'port' => $this->port,
                'path' => $this->prefix . $path,
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
    private function isTokenExpired(string $token): bool
    {
        $bufferInSeconds = 3;

        $currentTimestamp = time();
        $decoded = $this->decodeToken($token);

        return $currentTimestamp > ($decoded->exp - $bufferInSeconds);
    }

    /**
     * @param string $token
     * @return \stdClass
     */
    private function decodeToken(string $token): \stdClass
    {
        $tokenPayload = explode(".", $token)[1];

        return json_decode(base64_decode($tokenPayload));
    }
}
