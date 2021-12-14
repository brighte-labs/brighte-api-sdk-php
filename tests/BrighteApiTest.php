<?php

declare(strict_types=1);

namespace BrighteCapital\Tests\Api;

use BrighteCapital\Api\BrighteApi;
use Cache\Adapter\Common\CacheItem;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \BrighteCapital\Api\BrighteApi
 */
class BrighteApiTest extends \PHPUnit\Framework\TestCase
{

    /** @var \Psr\Http\Client\ClientInterface */
    protected $http;

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var \PHPUnit\Framework\MockObject\MockObject|CacheItemPoolInterface */
    protected $cache;

    /** @var \BrighteCapital\Api\BrighteApi */
    protected $api;

    /** @var string */
    protected $accessToken;

    /** @var string */
    protected $accessTokenExpired;

    protected function setUp(): void
    {
        parent::setUp();
        $config = [
            'uri' => 'https://api.brighte.com.au/v1',
            'client_id' => 'test-client',
            'client_secret' => 'client-secret',
        ];
        $this->http = $this->createMock(ClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->cache = $this->createMock(CacheItemPoolInterface::class);
        $this->api = new BrighteApi($this->http, $this->logger, $config, $this->cache);

        $tokenHeader = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9';
        $tokenSignature = 'vSOe9pC7ex0LwD5KM9_kp_jrXFLoTTKfvy959tHtU5g';

        $tokenPayload = [
            'userId' => '6',
            'remoteId' => 'U15',
            'firstName' => 'Andy',
            'lastName' => 'Fake',
            'email' => 'vendor@brighte.com.au',
            'mobile' => '0400000044',
            'role' => 'VENDOR',
            'roleId' => 3,
            'scope' => ["create:notifications"],
            'vendors' => [3, 7],
            'agent' => null,
            'iat' => 1639434967,
            'exp' => 1639434967,
            'aud' => 'https://brighte.com.au',
            'iss' => 'Brighte'
        ];

        $tokenPayloadEncoded = base64_encode(json_encode($tokenPayload, JSON_UNESCAPED_SLASHES));

        $this->accessTokenExpired = $tokenHeader . '.' . $tokenPayloadEncoded . '.' . $tokenSignature;

        $tokenPayload['exp'] = time() + 60;

        $tokenPayloadEncoded = base64_encode(json_encode($tokenPayload, JSON_UNESCAPED_SLASHES));

        $this->accessToken = $tokenHeader . '.' . $tokenPayloadEncoded . '.' . $tokenSignature;
    }

    /**
     * @covers ::__construct
     * @covers ::getCached
     * @covers ::get
     * @covers ::post
     * @covers ::doRequest
     * @covers ::authenticate
     * @covers ::getToken
     * @covers ::isTokenExpired
     * @covers ::decodeToken
     */
    public function testGet(): void
    {
        $expectApiRequest = new Request(
            'GET',
            new Uri('https://api.brighte.com.au/v1/chipmonks?size=0.5'),
            [
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'extra-header' => 'extra-header',
                'Authorization' => 'Bearer ' . $this->accessToken,
            ]
        );
        $this->cache->expects(self::once())->method('save');
        $authResponse = new Response(200, [], json_encode(['access_token' => $this->accessToken, 'expires_in' => 900]));
        $apiResponse = new Response(200, [], 'Sample Response');
        $apiFailResponse = new Response(401, [], 'Sample Response');
        $this->http->expects(self::exactly(5))->method('sendRequest')
            ->withConsecutive([self::isInstanceOf(Request::class)], [$expectApiRequest])
            ->willReturnOnConsecutiveCalls($authResponse, $apiResponse, $apiResponse, $apiFailResponse, $authResponse);
        // Authenticate, fill cache
        $this->assertInstanceOf(
            ResponseInterface::class,
            $result = $this->api->get('/chipmonks', 'size=0.5', ['extra-header' => 'extra-header'])
        );

        $this->assertEquals('200', $result->getStatusCode());

        // Use cache
        $this->assertInstanceOf(
            ResponseInterface::class,
            $result = $this->api->get('/chipmonks', 'size=0.5', ['extra-header' => 'extra-header'])
        );

        $this->assertEquals('200', $result->getStatusCode());

        // Use existing auth, get fresh
        $this->assertInstanceOf(
            ResponseInterface::class,
            $result = $this->api->get('/dangermouse', 'size=0.5', ['extra-header' => 'extra-header'])
        );

        $this->assertEquals('200', $result->getStatusCode());

        // Use existing auth, get fresh but failed (401).
        $this->assertInstanceOf(
            ResponseInterface::class,
            $result = $this->api->get('/mole', 'size=0.5', ['extra-header' => 'extra-header'])
        );

        $this->assertEquals('401', $result->getStatusCode());

        // Use existing auth, Using same failed resource again, but get fresh due to prior failure.
        $this->assertInstanceOf(
            ResponseInterface::class,
            $result = $this->api->get('/mole', 'size=0.5', ['extra-header' => 'extra-header'])
        );

        $this->assertEquals('200', $result->getStatusCode());
    }

    /**
     * @covers ::__construct
     * @covers ::getCached
     * @covers ::get
     * @covers ::post
     * @covers ::doRequest
     * @covers ::authenticate
     * @covers ::getToken
     * @covers ::isTokenExpired
     * @covers ::decodeToken
     */
    public function testJWTExpired(): void
    {
        $expectApiRequestExpired = new Request(
            'GET',
            new Uri('https://api.brighte.com.au/v1/chipmonks?size=0.5'),
            [
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'extra-header' => 'extra-header',
                'Authorization' => 'Bearer ' . $this->accessTokenExpired,
            ]
        );

        $expectApiRequestExpiredOther = new Request(
            'GET',
            new Uri('https://api.brighte.com.au/v1/dangermouse?size=0.5'),
            [
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'extra-header' => 'extra-header',
                'Authorization' => 'Bearer ' . $this->accessTokenExpired,
            ]
        );

        $this->cache->expects(self::exactly(2))->method('save');
        $authResponseExpired = new Response(
            200,
            [],
            json_encode(['access_token' => $this->accessTokenExpired, 'expires_in' => 900]),
        );
        $apiResponse = new Response(200, [], 'Sample Response');

        $this->http->expects(self::exactly(4))->method('sendRequest')
            ->withConsecutive(
                [self::isInstanceOf(Request::class)],
                [$expectApiRequestExpired],
                [self::isInstanceOf(Request::class)],
                [$expectApiRequestExpiredOther]
            )
            ->willReturnOnConsecutiveCalls($authResponseExpired, $apiResponse, $authResponseExpired, $apiResponse);

        // Authenticate, fill cache
        $this->assertInstanceOf(
            ResponseInterface::class,
            $result = $this->api->get('/chipmonks', 'size=0.5', ['extra-header' => 'extra-header'])
        );

        $this->assertEquals('200', $result->getStatusCode());

        // Use cache
        $this->assertInstanceOf(
            ResponseInterface::class,
            $result = $this->api->get('/chipmonks', 'size=0.5', ['extra-header' => 'extra-header'])
        );

        $this->assertEquals('200', $result->getStatusCode());

        // Use existing auth, get fresh
        $this->assertInstanceOf(
            ResponseInterface::class,
            $result = $this->api->get('/dangermouse', 'size=0.5', ['extra-header' => 'extra-header'])
        );

        $this->assertEquals('200', $result->getStatusCode());
    }

    /**
     * @covers ::__construct
     * @covers ::getCached
     * @covers ::get
     * @covers ::post
     * @covers ::doRequest
     * @covers ::authenticate
     * @covers ::getToken
     */
    public function testApiKeyAuthFail(): void
    {
        $config = [
            'uri' => 'https://api.brighte.com.au/v1',
            'key' => 'supersecretapikey',
        ];
        $this->api = new BrighteApi($this->http, $this->logger, $config, $this->cache);
        $authResponse = new Response(401, [], json_encode(['message' => 'API key mismatch']));
        $this->http->expects(self::exactly(1))->method('sendRequest')
            ->with(self::isInstanceOf(Request::class))
            ->willReturn($authResponse);
        // Authenticate but fail
        $this->expectException(\InvalidArgumentException::class);
        $this->api->get('/chipmonks', 'size=0.5', ['extra-header' => 'extra-header']);
    }

    /**
     * @covers ::__construct
     * @covers ::getCached
     * @covers ::get
     * @covers ::post
     * @covers ::doRequest
     * @covers ::authenticate
     * @covers ::getToken
     */
    public function testAuthFail(): void
    {
        $authResponse = new Response(401, [], json_encode(['error' => 'unauthorized_client']));
        $this->http->expects(self::exactly(1))->method('sendRequest')
            ->with(self::isInstanceOf(Request::class))
            ->willReturn($authResponse);
        // Authenticate but fail
        $this->expectException(\InvalidArgumentException::class);
        $this->api->get('/chipmonks', 'size=0.5', ['extra-header' => 'extra-header']);
    }

    /**
     * @covers ::__construct
     * @covers ::getCached
     * @covers ::get
     * @covers ::post
     * @covers ::doRequest
     * @covers ::authenticate
     * @covers ::getToken
     */
    public function testAuthCache(): void
    {
        $expectApiRequest = new Request(
            'GET',
            new Uri('https://api.brighte.com.au/v1/chipmonks'),
            [
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->accessToken,
            ]
        );
        $item = $this->createMock(CacheItemInterface::class);
        $item->expects(self::once())->method('get')->willReturn($this->accessToken);
        $this->cache->expects(self::once())->method('getItem')->with('service_jwt')->willReturn($item);
        $this->http->expects(self::exactly(1))->method('sendRequest')->with($expectApiRequest);
        $this->api->get('/chipmonks');
    }

    /**
     * @covers ::__construct
     * @covers ::post
     * @covers ::doRequest
     * @covers ::authenticate
     * @covers ::getToken
     */
    public function testPost(): void
    {
        $authResponse = new Response(200, [], json_encode(['access_token' => $this->accessToken]));
        $apiResponse = new Response(200, [], 'Sample Response');
        $this->http->expects(self::exactly(2))->method('sendRequest')
            ->with(self::isInstanceOf(Request::class))
            ->willReturnOnConsecutiveCalls($authResponse, $apiResponse);
        $this->assertInstanceOf(
            ResponseInterface::class,
            $this->api->post('/chipmonks', 'body', 'size=0.5', ['extra-header' => 'extra-header'])
        );
    }
}
