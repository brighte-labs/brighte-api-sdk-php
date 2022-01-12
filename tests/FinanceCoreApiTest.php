<?php

declare(strict_types=1);

namespace BrighteCapital\Tests\Api;

use BrighteCapital\Api\BrighteApi;
use BrighteCapital\Api\Models\ProductConfig;
use BrighteCapital\Api\FinanceCoreApi;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \BrighteCapital\Api\FinanceCoreApi
 */
class FinanceCoreApiTest extends \PHPUnit\Framework\TestCase
{

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var \BrighteCapital\Api\BrighteApi */
    protected $brighteApi;

    /** @var \BrighteCapital\Api\FinanceCoreApi */
    protected $financeCoreApi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->brighteApi = $this->createMock(BrighteApi::class);
        $this->financeCoreApi = new FinanceCoreApi($this->logger, $this->brighteApi);
    }

    /**
     * @covers ::__construct
     * @covers ::getProductConfig
     */
    public function testgetProductConfig(): void
    {
        $response = [
            'data' => [
                'getProductConfiguration' => [
                    'establishmentFee' => 4.99,
                    'interestRate' => 5.99,
                    'applicationFee' => 6.99,
                    'annualFee' => 7.99,
                    'weeklyAccountFee' => 8.99,
                    'latePaymentFee' => 9.99,
                    'introducerFee' => 10.99,
                    'enableExpressSettlement' => true,
                    'minimumFinanceAmount' => 11.99,
                    'maximumFinanceAmount' => 12.99,
                    'minRepaymentYear' => 13.99,
                    'maxRepaymentYear' => 14.99,
                    'forceCcaProcess' => true,
                    'defaultPaymentCycle' => 'weekly',
                    'invoiceRequired' => true,
                    'manualSettlementRequired' => true,
                    'version' => 1,
                    'fpAccountType' => 'savings',
                    'fpBranch'=> 'branch',
                ]
            ]
        ];

        $version = 1;
        $slug = 'GreenLoan';
        $vendorId = 'E1234567';

        $query = <<<GQL
            query {
                getProductConfiguration(
                version: {$version}
                vendorId: "{$vendorId}"
                slug: {$slug}
                ) {
                    interestRate
                    establishmentFee
                    applicationFee
                    annualFee
                    weeklyAccountFee
                    latePaymentFee
                    introducerFee
                    enableExpressSettlement
                    minimumFinanceAmount
                    maximumFinanceAmount
                    minRepaymentYear
                    maxRepaymentYear
                    forceCcaProcess
                    defaultPaymentCycle
                    invoiceRequired
                    manualSettlementRequired
                    fpBranch
                    fpAccountType
                }
            }
        GQL;   

        $expectedBody = [
            'query' => $query
        ];

        $response = new Response(200, [], json_encode($response));
        print(json_encode($expectedBody));
        $this->brighteApi->expects(self::once())->method('post')
            ->with('../v2/finance/graphql', json_encode($expectedBody))
            ->willReturn($response);
        $config = $this->financeCoreApi->getProductConfig($slug, $vendorId, $version);
        self::assertInstanceOf(ProductConfig::class, $config);
        self::assertEquals(4.99, $config->establishmentFee);
        self::assertEquals(5.99, $config->interestRate);
        self::assertEquals(6.99, $config->applicationFee);
        self::assertEquals(7.99, $config->annualFee);
        self::assertEquals(8.99, $config->weeklyAccountFee);
        self::assertEquals(9.99, $config->latePaymentFee);
        self::assertEquals(10.99, $config->introducerFee);
        self::assertEquals(true, $config->enableExpressSettlement);
        self::assertEquals(11.99, $config->minimumFinanceAmount);
        self::assertEquals(12.99, $config->maximumFinanceAmount);
        self::assertEquals(13.99, $config->minRepaymentYear);
        self::assertEquals(14.99, $config->maxRepaymentYear);
        self::assertEquals(true, $config->forceCcaProcess);
        self::assertEquals('weekly', $config->defaultPaymentCycle);
        self::assertEquals(1, $config->version);
        self::assertEquals('savings', $config->fpAccountType);
        self::assertEquals('branch', $config->fpBranch);
    }

    /**
     * @covers ::__construct
     * @covers ::getProductConfig
     * @covers ::logResponse
     */
    public function testgetProductConfigFail(): void
    {
        $response = new Response(404, [], json_encode(['message' => 'Not found']));
        $this->logger->expects(self::once())->method('warning')->with(
            'BrighteCapital\Api\AbstractApi->getProductConfig: 404: Not found'
        );
        $this->brighteApi->expects(self::once())->method('post')
            ->with('../v2/finance/graphql')->willReturn($response);
        $config = $this->financeCoreApi->getProductConfig('slug');
        self::assertNull($config);
    }
}
