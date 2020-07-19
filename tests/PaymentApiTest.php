<?php

declare(strict_types=1);

namespace BrighteCapital\Tests\Api;

use BrighteCapital\Api\BrighteApi;
use BrighteCapital\Api\Models\PaymentMethod;
use BrighteCapital\Api\PaymentApi;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \BrighteCapital\Api\PaymentApi
 */
class PaymentApiTest extends \PHPUnit\Framework\TestCase
{

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var \BrighteCapital\Api\BrighteApi */
    protected $brighteApi;

    /** @var \BrighteCapital\Api\PaymentApi */
    protected $PaymentApi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->brighteApi = $this->createMock(BrighteApi::class);
        $this->paymentApi = new PaymentApi($this->logger, $this->brighteApi);
    }

    /**
     * @covers ::__construct
     * @covers ::getMethod
     */
    public function testgetMethod(): void
    {
        $providedMethod = [
            'id' => 'method-id',
            'userId' => 1,
            'type' => 'card',
            'cardHolder' => 'Joe Customer',
            'cardNumber' => '123x-xxxx-xxxx-1234',
            'cardType' => 'VISA',
            'cardExpiry' => '10/22',
            'accountNumber' => '1234567890',
            'accountLast4' => '1234',
            'accountName' => 'Joe Customer',
            'accountBsb' => '123456',
            'agreementText' => 'I agree to the terms',
            'source' => 'BOOP',
        ];
        $response = new Response(200, [], json_encode($providedMethod));
        $this->brighteApi->expects(self::once())->method('get')
            ->with('/payment-methods/method-id', 'userId=1')->willReturn($response);
        $method = $this->paymentApi->getMethod('method-id', 1);
        self::assertInstanceOf(PaymentMethod::class, $method);
        self::assertEquals('method-id', $method->id);
        self::assertEquals(1, $method->userId);
        self::assertEquals('card', $method->type);
        self::assertEquals('Joe Customer', $method->cardHolder);
        self::assertEquals('123x-xxxx-xxxx-1234', $method->cardNumber);
        self::assertEquals('VISA', $method->cardType);
        self::assertEquals('10/22', $method->cardExpiry);
        self::assertEquals('Joe Customer', $method->accountName);
        self::assertEquals('1234567890', $method->accountNumber);
        self::assertEquals('123456', $method->accountBsb);
        self::assertEquals('1234', $method->accountLast4);
        self::assertEquals('I agree to the terms', $method->agreementText);
        self::assertEquals('BOOP', $method->source);
    }

    /**
     * @covers ::__construct
     * @covers ::getMethod
     * @covers ::logResponse
     */
    public function testgetMethodFail(): void
    {
        $response = new Response(404, [], json_encode(['message' => 'Not found']));
        $this->logger->expects(self::once())->method('warning')->with(
            'BrighteCapital\Api\AbstractApi->getMethod: 404: Not found'
        );
        $this->brighteApi->expects(self::once())->method('get')
            ->with('/payment-methods/method-id')->willReturn($response);
        $method = $this->paymentApi->getMethod('method-id', 1);
        self::assertNull($method);
    }
}
