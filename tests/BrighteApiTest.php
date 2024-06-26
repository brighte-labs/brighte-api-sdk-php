<?php

declare(strict_types=1);

namespace BrighteCapital\Tests\Api;

use BrighteCapital\Api\BrighteApi;
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

    private const BEARER = 'Bearer ';
    private const SAMPLE_RESPONSE = 'Sample Response';
    private const URL_CHIPMONKS = '/chipmonks';
    private const URL_DANGER_MOUSE = '/dangermouse';
    private const URL_MOLE = '/mole';
    private const URL_PARAM_SIZE = 'size=0.5';

    protected function setUp(): void
    {
        parent::setUp();
        $config = [
            'uri' => 'https://api.brighte.com.au/v1',
            'client_id' => 'test-client',
            'client_secret' => 'client-secret',
            'auth0_domain' => 'fake-auth0-domain'
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
     * @return array
     */
    public function getProvider()
    {
        return [
            [self::URL_CHIPMONKS, '200', 2],
            [self::URL_DANGER_MOUSE, '200', 2],
            [self::URL_MOLE, '401', 2],
            [self::URL_MOLE, '200', 2],
        ];
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
     * @covers ::buildAudience
     * @covers ::cleanAudience
     * @covers ::setUri
     * @dataProvider getProvider
     */
    public function testGet($url, $statusCode, $sendRequestCalled): void
    {
        $expectApiRequest = new Request(
            'GET',
            new Uri('https://api.brighte.com.au/v1' . $url . '?size=0.5'),
            [
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'extra-header' => 'extra-header',
                'Authorization' => self::BEARER . $this->accessToken,
                'User-Agent' => 'BrighteSDK/1',
            ]
        );

        $blankCache = $this->createMock(CacheItemInterface::class);
        $this->cache->method('getItem')->willReturn($blankCache);
        $this->cache->expects(self::once())->method('save');

        $authResponse = new Response(200, [], json_encode(['access_token' => $this->accessToken, 'expires_in' => 900]));
        $apiResponse = new Response(200, [], self::SAMPLE_RESPONSE);
        $apiFailResponse = new Response(
            401,
            [],
            json_encode(['access_token' => $this->accessToken, 'expires_in' => 900])
        );

        $secondCall = $apiResponse;
        if ($statusCode !== '200') {
            $secondCall = $apiFailResponse;
        }

        $this->http->expects(self::exactly($sendRequestCalled))->method('sendRequest')
            ->withConsecutive([self::isInstanceOf(Request::class)], [$expectApiRequest])
            ->willReturnOnConsecutiveCalls($authResponse, $secondCall);


        // Authenticate, fill cache on first call
        $this->assertInstanceOf(
            ResponseInterface::class,
            $result = $this->api->get($url, self::URL_PARAM_SIZE, ['extra-header' => 'extra-header'], $url)
        );

        $this->assertEquals($statusCode, $result->getStatusCode());
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
     * @covers ::buildAudience
     * @covers ::cleanAudience
     * @covers ::setUri
     */
    public function testJWTExpired(): void
    {
        $uriData = [
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'extra-header' => 'extra-header',
            'Authorization' => self::BEARER . $this->accessTokenExpired,
            'User-Agent' => 'BrighteSDK/1',
        ];

        $expectApiRequestExpired = new Request(
            'GET',
            new Uri('https://api.brighte.com.au/v1/chipmonks?size=0.5'),
            $uriData
        );

        $expectApiRequestExpiredOther = new Request(
            'GET',
            new Uri('https://api.brighte.com.au/v1/dangermouse?size=0.5'),
            $uriData
        );

        $blankCache = $this->createMock(CacheItemInterface::class);
        $this->cache->method('getItem')->willReturn($blankCache);
        $this->cache->expects(self::exactly(2))->method('save');
        $authResponseExpired = new Response(
            200,
            [],
            json_encode(['access_token' => $this->accessTokenExpired, 'expires_in' => 900])
        );
        $apiResponse = new Response(200, [], self::SAMPLE_RESPONSE);

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
            $result = $this->api->get(self::URL_CHIPMONKS, self::URL_PARAM_SIZE, ['extra-header' => 'extra-header'], self::URL_CHIPMONKS)
        );

        $this->assertEquals('200', $result->getStatusCode());

        // Use existing auth, get fresh
        $this->assertInstanceOf(
            ResponseInterface::class,
            $result = $this->api->get(self::URL_DANGER_MOUSE, self::URL_PARAM_SIZE, ['extra-header' => 'extra-header'], self::URL_DANGER_MOUSE)
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
     * @covers ::buildAudience
     * @covers ::cleanAudience
     * @covers ::setUri
     */
    public function testApiKeyAuthFail(): void
    {
        $config = [
            'uri' => 'https://api.brighte.com.au/v1',
            'key' => 'supersecretapikey',
            'auth0_domain' => 'fake-auth0-domain',
        ];
        $this->api = new BrighteApi($this->http, $this->logger, $config, $this->cache);
        $authResponse = new Response(401, [], json_encode(['message' => 'API key mismatch']));
        $this->http->expects(self::exactly(1))->method('sendRequest')
            ->with(self::isInstanceOf(Request::class))
            ->willReturn($authResponse);
        // Authenticate but fail
        $this->expectException(\InvalidArgumentException::class);
        $this->api->get(self::URL_CHIPMONKS, self::URL_PARAM_SIZE, ['extra-header' => 'extra-header'], self::URL_CHIPMONKS);
    }

    /**
     * @covers ::__construct
     * @covers ::getCached
     * @covers ::get
     * @covers ::post
     * @covers ::doRequest
     * @covers ::authenticate
     * @covers ::getToken
     * @covers ::buildAudience
     * @covers ::cleanAudience
     * @covers ::setUri
     */
    public function testAuthFail(): void
    {
        $authResponse = new Response(401, [], json_encode(['error' => 'unauthorized_client']));
        $this->http->expects(self::exactly(1))->method('sendRequest')
            ->with(self::isInstanceOf(Request::class))
            ->willReturn($authResponse);
        // Authenticate but fail
        $this->expectException(\InvalidArgumentException::class);
        $this->api->get(self::URL_CHIPMONKS, self::URL_PARAM_SIZE, ['extra-header' => 'extra-header'], self::URL_CHIPMONKS);
    }

    /**
     * @covers ::__construct
     * @covers ::getCached
     * @covers ::get
     * @covers ::post
     * @covers ::doRequest
     * @covers ::authenticate
     * @covers ::getToken
     * @covers ::buildAudience
     * @covers ::cleanAudience
     * @covers ::setUri
     */
    public function testAuthCache(): void
    {
        $expectApiRequest = new Request(
            'GET',
            new Uri('https://api.brighte.com.au/v1/chipmonks'),
            [
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'Authorization' => self::BEARER . $this->accessToken,
                'User-Agent' => 'BrighteSDK/1',
            ]
        );
        $item = $this->createMock(CacheItemInterface::class);
        $item->expects(self::once())->method('get')->willReturn($this->accessToken);
        $this->cache->expects(self::once())->method('getItem')->with('test-client_service_jwt_api.brighte.com.au_v1_chipmonks')->willReturn($item);
        $this->http->expects(self::exactly(1))->method('sendRequest')->with($expectApiRequest);
        $this->api->get(self::URL_CHIPMONKS, '', [], self::URL_CHIPMONKS);
    }

    /**
     * @covers ::__construct
     * @covers ::post
     * @covers ::doRequest
     * @covers ::authenticate
     * @covers ::getToken
     * @covers ::buildAudience
     * @covers ::cleanAudience
     * @covers ::setUri
     */
    public function testPost(): void
    {

        $blankCache = $this->createMock(CacheItemInterface::class);
        $this->cache->method('getItem')->willReturn($blankCache);
        $authResponse = new Response(200, [], json_encode(['access_token' => $this->accessToken]));
        $apiResponse = new Response(200, [], self::SAMPLE_RESPONSE);
        $this->http->expects(self::exactly(2))->method('sendRequest')
            ->with(self::isInstanceOf(Request::class))
            ->willReturnOnConsecutiveCalls($authResponse, $apiResponse);
        $this->assertInstanceOf(
            ResponseInterface::class,
            $this->api->post(self::URL_CHIPMONKS, 'body', self::URL_PARAM_SIZE, ['extra-header' => 'extra-header'], self::URL_CHIPMONKS)
        );
    }

    /**
     * @covers ::__construct
     * @covers ::cachedPost
     * @covers ::checkIfContainsError
     * @covers ::logGraphqlResponse
     * @covers ::doRequest
     * @covers ::authenticate
     * @covers ::getToken
     * @covers ::buildAudience
     * @covers ::cleanAudience
     * @covers ::setUri
     */
    public function testCachedPostWhenLocalCacheHits(): void
    {
        $expected = ['key' => 'value'];
        $apiResponse = new Response(200, [], json_encode($expected));

        $functionName = 'getFinancialProductConfig';
        $parameters = ['p1', 'p2'];

        $cachedToken = $this->createMock(CacheItemInterface::class);
        $cachedToken->expects(self::once())->method('get')->willReturn($this->accessToken);

        $this->http->expects(self::exactly(1))->method('sendRequest')
            ->with(self::isInstanceOf(Request::class))->willReturn($apiResponse);

        $this->cache->expects(self::exactly(2))->method('getItem')->withConsecutive(
            ['test-client_service_jwt_api.brighte.com.au_v1_chipmonks'],
            ['getFinancialProductConfig_p1_p2']
        )->willReturn($cachedToken);
        $this->cache->expects(self::once())->method('save'); // saves the value to the cache pool

        $this->api->cachedPost($functionName, $parameters, self::URL_CHIPMONKS, 'body', '', [], self::URL_CHIPMONKS, true);
        // Post a second time, local cache should respond.
        $actual = $this->api->cachedPost($functionName, $parameters, self::URL_CHIPMONKS, 'body', '', [], self::URL_CHIPMONKS);
        self::assertEquals((object) $expected, $actual);
    }

    /**
     * @covers ::__construct
     * @covers ::cachedPost
     * @covers ::checkIfContainsError
     * @covers ::logGraphqlResponse
     * @covers ::setUri
     */
    public function testCachedPostWhenCacheHits(): void
    {
        $functionName = 'getFinancialProductConfig';
        $parameters = ['p1', 'p2'];
        $expected = [
            'key' => 'value',
        ];
        $item = $this->createMock(CacheItemInterface::class);
        $item->expects(self::once())->method('get')->willReturn($expected);
        $this->cache->expects(self::once())->method('getItem')->willReturn($item);
        $this->cache->expects(self::once())->method('hasItem')->willReturn(true);
        $this->cache->expects(self::never())->method('save');
        $actual = $this->api->cachedPost($functionName, $parameters, self::URL_CHIPMONKS, 'body', '', [], self::URL_CHIPMONKS);
        self::assertEquals((array) $expected, $actual);
    }

    /**
     * @covers ::__construct
     * @covers ::cachedPost
     * @covers ::checkIfContainsError
     * @covers ::logGraphqlResponse
     * @covers ::doRequest
     * @covers ::authenticate
     * @covers ::getToken
     * @covers ::post
     * @covers ::buildAudience
     * @covers ::cleanAudience
     * @covers ::setUri
     */
    public function testCachedPostWhenCacheMiss(): void
    {
        $authResponse = new Response(200, [], json_encode(['access_token' => $this->accessToken]));
        $expected = ['key' => 'value'];
        $apiResponse = new Response(200, [], json_encode($expected));

        $functionName = 'getFinancialProductConfig';
        $parameters = ['p1', 'p2'];
        $blankCache = $this->createMock(CacheItemInterface::class);
        $this->cache->method('getItem')->willReturn($blankCache);

        $this->http->expects(self::exactly(2))->method('sendRequest')
            ->with(self::isInstanceOf(Request::class))
            ->willReturnOnConsecutiveCalls($authResponse, $apiResponse);
        $this->cache->expects(self::once())->method('hasItem')->willReturn(false);
        $this->cache->expects(self::exactly(2))->method('save');
        $actual = $this->api->cachedPost($functionName, $parameters, self::URL_CHIPMONKS, 'body', '', [], self::URL_CHIPMONKS);
        $this->assertIsObject($actual);
        self::assertEquals((array) $actual, $expected);
    }


    /**
     * @covers ::__construct
     * @covers ::cachedPost
     * @covers ::checkIfContainsError
     * @covers ::logGraphqlResponse
     * @covers ::doRequest
     * @covers ::authenticate
     * @covers ::getToken
     * @covers ::post
     * @covers ::buildAudience
     * @covers ::cleanAudience
     * @covers ::setUri
     * @dataProvider cachedPostResponseProvider
     */
    public function testCachedPostWhenGraphqlError($authResponse, $apiResponse, $message): void
    {
        $this->logger->expects(self::once())->method('warning')->with(
            "BrighteCapital\Api\BrighteApi->getFinancialProductConfig: " . $message
        );

        $blankCache = $this->createMock(CacheItemInterface::class);
        $this->cache->method('getItem')->willReturn($blankCache);
        $this->http->expects(self::exactly(2))->method('sendRequest')
            ->with(self::isInstanceOf(Request::class))
            ->willReturnOnConsecutiveCalls($authResponse, $apiResponse);

        $this->cache->expects(self::once())->method('hasItem')->willReturn(false);
        $this->cache->expects(self::once())->method('save');
        $functionName = 'getFinancialProductConfig';
        $parameters = ['p1', 'p2'];
        $actual = $this->api->cachedPost($functionName, $parameters, self::URL_CHIPMONKS, 'body', '', [], self::URL_CHIPMONKS);
        self::assertNull($actual);
    }

    private function createGraphqlErrorResponse(string $message)
    {
        $body = [
            "errors" => [
                [
                    "message" => $message,
                    "extensions" => [
                        "code" => "404",
                        "response" => [
                            "statusCode" => 404,
                            "message" => $message,
                            "error" => "Not Found",
                        ]
                    ]
                ]
            ],
            "data" => null,
        ];

        return new Response(200, [], json_encode($body));
    }

    public function cachedPostResponseProvider()
    {
        $authResponse = new Response(200, [], json_encode(['access_token' => 'token']));
        $notFoundMessage = "Financial product configuration not found for slug" .
            " 'brighte-green-loan-energy', version 1 and vendorPublicId 'E81'";
        $networkErrorMessage = 'Gateway Time-out';

        return [
            [
                $authResponse,
                $this->createGraphqlErrorResponse($notFoundMessage),
                "200: " . $notFoundMessage
            ],
            [
                $authResponse,
                new Response(504, [], json_encode(['message' => $networkErrorMessage])),
                "504: Gateway Time-out"
            ]
        ];
    }
}
