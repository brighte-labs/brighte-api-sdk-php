<?php

declare(strict_types=1);

namespace BrighteCapital\Tests\Api;

use BrighteCapital\Api\BrighteApi;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Slim\Psr7\Uri;

/**
 * @coversDefaultClass \BrighteCapital\Api\BrighteApi
 */
class BrighteApiTest extends \PHPUnit\Framework\TestCase
{

    /** @var \Psr\Http\Client\ClientInterface */
    protected $http;

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var \BrighteCapital\Api\BrighteApi */
    protected $api;

    protected function setUp(): void
    {
        parent::setUp();
        $config = [
            'uri' => 'https://api.brighte.com.au/v1',
            'key' => 'theapikeysecretnottoshare',
        ];
        $this->http = $this->createMock(ClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->api = new BrighteApi($this->http, $this->logger, $config);
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
    public function testGet(): void
    {
        $accessToken = 'SLf:$*h$5fpj(#*pa';
        $expectApiRequest = new Request(
            'GET',
            new Uri('https', 'api.brighte.com.au', null, '/v1/chipmonks', 'size=0.5'),
            [
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'extra-header' => 'extra-header',
                'Authorization' => 'Bearer ' . $accessToken,
            ]
        );
        $authResponse = new Response(200, [], json_encode(compact('accessToken')));
        $apiResponse = new Response(200, [], 'Sample Response');
        $this->http->expects(self::exactly(3))->method('sendRequest')
            ->withConsecutive([self::isInstanceOf(Request::class)], [$expectApiRequest])
            ->willReturnOnConsecutiveCalls($authResponse, $apiResponse, $apiResponse);
        // Authenticate, fill cache
        $this->assertInstanceOf(
            ResponseInterface::class,
            $this->api->get('/chipmonks', 'size=0.5', ['extra-header' => 'extra-header'])
        );
        // Use cache
        $this->assertInstanceOf(
            ResponseInterface::class,
            $this->api->get('/chipmonks', 'size=0.5', ['extra-header' => 'extra-header'])
        );
        // Use existing auth, get fresh
        $this->assertInstanceOf(
            ResponseInterface::class,
            $this->api->get('/dangermouse', 'size=0.5', ['extra-header' => 'extra-header'])
        );
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
     * @covers ::post
     * @covers ::doRequest
     * @covers ::authenticate
     * @covers ::getToken
     */
    public function testPost(): void
    {
        $accessToken = 'SLf:$*h$5fpj(#*pa';
        $authResponse = new Response(200, [], json_encode(compact('accessToken')));
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
