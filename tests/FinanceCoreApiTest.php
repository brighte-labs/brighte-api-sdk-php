<?php

declare(strict_types=1);

namespace BrighteCapital\Tests\Api;

use BrighteCapital\Api\BrighteApi;
use BrighteCapital\Api\Models\FinancialProductConfig;
use BrighteCapital\Api\Models\FinancialProduct;
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
     * @covers ::getFinancialProductConfig
     */
    public function testgetFinancialProductConfig(): void
    {
        $response = [
            'data' => [
                'financialProductConfiguration' => [
                    'establishmentFee' => 4.99,
                    'interestRate' => 5.99,
                    'applicationFee' => 6.99,
                    'annualFee' => 7.99,
                    'weeklyAccountFee' => 8.99,
                    'latePaymentFee' => 9.99,
                    'introducerFee' => 10.99,
                    'enableExpressSettlement' => true,
                    'minFinanceAmount' => 11.99,
                    'maxFinanceAmount' => 12.99,
                    'minRepaymentMonth' => 13,
                    'maxRepaymentMonth' => 30,
                    'forceCcaProcess' => true,
                    'defaultPaymentCycle' => 'weekly',
                    'invoiceRequired' => true,
                    'manualSettlementRequired' => true,
                    'version' => 1,
                ]
            ]
        ];

        $version = 1;
        $slug = 'GreenLoan';
        $vendorId = 'E1234567';

        $query = <<<GQL
            query {
                financialProductConfiguration(
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
                    minFinanceAmount
                    maxFinanceAmount
                    minRepaymentMonth
                    maxRepaymentMonth
                    forceCcaProcess
                    defaultPaymentCycle
                    invoiceRequired
                    manualSettlementRequired
                }
            }
GQL;

        $expectedBody = [
            'query' => $query
        ];

        $response = new Response(200, [], json_encode($response));
        $this->brighteApi->expects(self::once())->method('post')
            ->with('../v2/finance/graphql', json_encode($expectedBody))
            ->willReturn($response);
        $config = $this->financeCoreApi->getFinancialProductConfig($slug, $vendorId, $version);
        self::assertInstanceOf(FinancialProductConfig::class, $config);
        self::assertEquals(4.99, $config->establishmentFee);
        self::assertEquals(5.99, $config->interestRate);
        self::assertEquals(6.99, $config->applicationFee);
        self::assertEquals(7.99, $config->annualFee);
        self::assertEquals(8.99, $config->weeklyAccountFee);
        self::assertEquals(9.99, $config->latePaymentFee);
        self::assertEquals(10.99, $config->introducerFee);
        self::assertEquals(true, $config->enableExpressSettlement);
        self::assertEquals(11.99, $config->minFinanceAmount);
        self::assertEquals(12.99, $config->maxFinanceAmount);
        self::assertEquals(13, $config->minRepaymentMonth);
        self::assertEquals(30, $config->maxRepaymentMonth);
        self::assertEquals(true, $config->forceCcaProcess);
        self::assertEquals('weekly', $config->defaultPaymentCycle);
        self::assertEquals(1, $config->version);
    }

        /**
     * @covers ::__construct
     * @covers ::getFinancialProduct
     */
    public function testgetFinancialProduct(): void
    {
        $response = [
            'data' => [
                'financialProduct' => [
                    'slug' => 'GreenLoan',
                    'name' => 'test-product',
                    'type' => 'loan',
                    'customerType' => 'residential',
                    'loanTypeId' => 1,
                    'categoryGroup' => 'green',
                    'fpAccountType' => 'test-fp-account-type',
                    'fpBranch' => 'test-fp-branch',
                    'configuration' => [
                        'establishmentFee' => 4.99,
                        'interestRate' => 5.99,
                        'applicationFee' => 6.99,
                        'annualFee' => 7.99,
                        'weeklyAccountFee' => 8.99,
                        'latePaymentFee' => 9.99,
                        'introducerFee' => 10.99,
                        'enableExpressSettlement' => true,
                        'minFinanceAmount' => 11.99,
                        'maxFinanceAmount' => 12.99,
                        'minRepaymentMonth' => 13,
                        'maxRepaymentMonth' => 30,
                        'forceCcaProcess' => true,
                        'defaultPaymentCycle' => 'weekly',
                        'invoiceRequired' => true,
                        'manualSettlementRequired' => true,
                        'version' => 1,
                    ]
                ]
            ]
        ];

        $slug = 'GreenLoan';

        $query = <<<GQL
            query {
                financialProduct(
                slug: {$slug}
                ) {
                    slug
                    name
                    type
                    customerType
                    loanTypeId
                    configuration {
                      interestRate
                      establishmentFee
                      applicationFee
                      annualFee
                      weeklyAccountFee
                      latePaymentFee
                      introducerFee
                      enableExpressSettlement
                      minFinanceAmount
                      maxFinanceAmount
                      minRepaymentMonth
                      maxRepaymentMonth
                      forceCcaProcess
                      defaultPaymentCycle
                      invoiceRequired
                      manualSettlementRequired
                    }
                    categoryGroup
                    fpAccountType
                    fpBranch
                }
            }
GQL;

        $expectedBody = [
            'query' => $query
        ];

        $response = new Response(200, [], json_encode($response));
        $this->brighteApi->expects(self::once())->method('post')
            ->with('../v2/finance/graphql', json_encode($expectedBody))
            ->willReturn($response);
        $product = $this->financeCoreApi->getFinancialProduct($slug);
        $config = $product->configuration;
        self::assertInstanceOf(FinancialProduct::class, $product);
        self::assertEquals('GreenLoan', $product->slug);
        self::assertEquals('test-product', $product->name);
        self::assertEquals('loan', $product->type);
        self::assertEquals('residential', $product->customerType);
        self::assertEquals(1, $product->loanTypeId);
        self::assertEquals('green', $product->categoryGroup);
        self::assertEquals('test-fp-account-type', $product->fpAccountType);
        self::assertEquals('test-fp-branch', $product->fpBranch);
        self::assertEquals(4.99, $config->establishmentFee);
        self::assertEquals(5.99, $config->interestRate);
        self::assertEquals(6.99, $config->applicationFee);
        self::assertEquals(7.99, $config->annualFee);
        self::assertEquals(8.99, $config->weeklyAccountFee);
        self::assertEquals(9.99, $config->latePaymentFee);
        self::assertEquals(10.99, $config->introducerFee);
        self::assertEquals(true, $config->enableExpressSettlement);
        self::assertEquals(11.99, $config->minFinanceAmount);
        self::assertEquals(12.99, $config->maxFinanceAmount);
        self::assertEquals(13, $config->minRepaymentMonth);
        self::assertEquals(30, $config->maxRepaymentMonth);
        self::assertEquals(true, $config->forceCcaProcess);
        self::assertEquals('weekly', $config->defaultPaymentCycle);
        self::assertEquals(1, $config->version);
    }

    /**
    * @covers ::__construct
    * @covers ::getFinancialProductConfig
    * @covers ::logResponse
    */
    public function testgetProductConfigFail(): void
    {
        $response = new Response(404, [], json_encode(['message' => 'Not found']));
        $this->logger->expects(self::once())->method('warning')->with(
            'BrighteCapital\Api\AbstractApi->getFinancialProductConfig: 404: Not found'
        );
        $this->brighteApi->expects(self::once())->method('post')
        ->with('../v2/finance/graphql')->willReturn($response);
        $config = $this->financeCoreApi->getFinancialProductConfig('slug');
        self::assertNull($config);
    }
}
