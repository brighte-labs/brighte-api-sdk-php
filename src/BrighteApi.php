<?php

declare(strict_types = 1);

namespace BrighteCapital\Api;

use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Slim\Psr7\Uri as SlimUri;

class BrighteApi
{

    /** @var string scheme */
    protected $scheme;

    /** @var string host */
    protected $host;

    /** @var string prefix */
    protected $prefix;

    /** @var int port */
    protected $port;

    /** @var string apiKey */
    protected $apiKey;

    /** @var string accessToken */
    protected $accessToken;

    /** @var \Psr\Http\Client\ClientInterface HTTP client */
    protected $http;

    /** @var \Psr\Log\LoggerInterface Logger */
    protected $logger;

    /** @var (string|int|bool)[][] */
    protected $cache = [];

    /**
     * @param \Psr\Http\Client\ClientInterface $http HTTP client
     * @param \Psr\Log\LoggerInterface $log Logger
     * @param (string|int)[] $config configuration for API
     * @Inject({"config"="settings.brighteApi"})
     */
    public function __construct(
        ClientInterface $http,
        LoggerInterface $log,
        array $config
    )
    {
        $uri = new Uri($config['uri']);
        $this->scheme = $uri->getScheme();
        $this->host = $uri->getHost();
        $this->prefix = $uri->getPath();
        $this->port = $uri->getPort();
        $this->apiKey = $config['key'];
        $this->http = $http;
        $this->log = $log;
    }

    protected function getToken(): string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $this->authenticate();

        return $this->accessToken;
    }

    protected function authenticate(): void
    {
        $response = $this->post('/identity/authenticate', json_encode(['apiKey' => $this->apiKey]), '', [], false);
        $body = json_decode((string) $response->getBody());

        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            throw new \InvalidArgumentException($body->message);
        }

        $this->accessToken = $body->accessToken;
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
        return $this->cache[$key] ?? ($this->cache[$key] = $callback(...$args));
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
    ): ResponseInterface
    {
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
    ): ResponseInterface
    {
        $this->log->debug(
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

        return $this->http->sendRequest(new Request(
            $method,
            new SlimUri(
                $this->scheme,
                $this->host,
                $this->port,
                $this->prefix . $path,
                $query
            ),
            $headers,
            $body
        ));
    }

}
