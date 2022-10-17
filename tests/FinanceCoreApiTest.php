<?php

declare(strict_types=1);

namespace BrighteCapital\Tests\Api;

use BrighteCapital\Api\BrighteApi;
use BrighteCapital\Api\Models\FinancialProductConfig;
use BrighteCapital\Api\Models\FinancialProduct;
use BrighteCapital\Api\FinanceCoreApi;
use BrighteCapital\Api\Models\FinanceCore\Vendor as FinanceCoreVendor;
use BrighteCapital\Api\Models\FinanceCore\VendorRebate;
use Psr\Log\LoggerInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @coversDefaultClass \BrighteCapital\Api\FinanceCoreApi
 */
class FinanceCoreApiTest extends \PHPUnit\Framework\TestCase
{

    public const PATH = '/../v2/finance/graphql';

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var \BrighteCapital\Api\BrighteApi */
    protected $brighteApi;

    /** @var \BrighteCapital\Api\FinanceCoreApi */
    protected $financeCoreApi;

    private $expectedConfig;

    private $expectedConfigResponse;

    /** @var \PHPUnit\Framework\MockObject\MockObject|CacheItemPoolInterface */
    protected $cache;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->expectedConfig = [
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
        ];

        $this->expectedConfigResponse = [
            'data' => [
                'financialProductConfiguration' => $this->expectedConfig,
            ]
        ];

        $this->expectedVendor = [
            'legacyId' => 1,
            'publicId' => 'E1',
            'tradingName' => 'Test Company',
            'sfAccountId' => 'fakeTestAccount',
            'slug' => 'test-company',
            'activeRebate' => new VendorRebate([
                'startDate' => '2022-10-04T23:22:34.000Z',
                'finishDate' => '2025-10-04T23:22:34.000Z',
                'dollar' => 500,
                'percentage' => null,
            ])
        ];

        $this->expectedVendorResponse = [
            'data' => [
                'vendor' => $this->expectedVendor,
            ]
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->brighteApi = $this->createMock(BrighteApi::class);
        $this->financeCoreApi = new FinanceCoreApi($this->logger, $this->brighteApi);
    }

    public function financialProductConfigProvider()
    {
        return [
            [
                ['GreenLoan', null, null],
                $this->expectedConfigResponse,
            ],
            [
                ['GreenLoan', 'test-vendor-id', null],
                $this->expectedConfigResponse,
            ],
            [
                ['GreenLoan', null, 1],
                $this->expectedConfigResponse,
            ],
            [
                ['GreenLoan', 'test-vendor-id', 1],
                $this->expectedConfigResponse,
            ],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::getFinancialProductConfig
     * @covers ::getFinancialProductConfigFromResponse
     * @covers ::createGetFinancialProductConfigQuery
     * @dataProvider financialProductConfigProvider
     */
    public function testgetFinancialProductConfig($input, $response): void
    {
        $slug = $input[0];
        $vendorId = $input[1];
        $version = $input[2];

        $query = $this->financeCoreApi->createGetFinancialProductConfigQuery($slug, $vendorId, $version);

        $expectedBody = [
            'query' => $query
        ];

        $this->brighteApi->expects(self::once())->method('cachedPost')
            ->with('getFinancialProductConfig', $input, self::PATH, json_encode($expectedBody))
            ->willReturn(json_decode(json_encode($response)));
        $config = $this->financeCoreApi->getFinancialProductConfig($slug, $vendorId, $version);
        self::assertInstanceOf(FinancialProductConfig::class, $config);
        self::assertEquals($this->expectedConfig, (array)$config);
    }

    /**
     * @covers ::__construct
     * @covers ::getFinancialProduct
     * @covers ::getFinancialProductConfigFromResponse
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
                    'configuration' => $this->expectedConfig,
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
                      version
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

        $this->brighteApi->expects(self::once())->method('cachedPost')
            ->with('getFinancialProduct', [$slug], self::PATH, json_encode($expectedBody))
            ->willReturn(json_decode(json_encode($response)));
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
        self::assertEquals($this->expectedConfig, (array)$config);
    }

    /**
     * @covers ::__construct
     * @covers ::getFinancialProductConfig
     * @covers ::getFinancialProductConfigFromResponse
     * @covers ::createGetFinancialProductConfigQuery
     */
    public function testgetFinancialProductConfigWhenReturnsNull(): void
    {
        $this->brighteApi
            ->expects(self::once())->method('cachedPost')
            ->willReturn(null);
        $config = $this->financeCoreApi->getFinancialProductConfig('GreenLoan');
        self::assertNull($config);
    }

    /**
     * @covers ::__construct
     * @covers ::getFinancialProduct
     * @covers ::getFinancialProductConfigFromResponse
     */
    public function testgetFinancialProductWhenReturnsNull(): void
    {
        $this->brighteApi
            ->expects(self::once())->method('cachedPost')
            ->willReturn(null);
        $config = $this->financeCoreApi->getFinancialProduct('GreenLoan');
        self::assertNull($config);
    }

        /**
     * @covers ::__construct
     * @covers ::getVendor
     * @covers ::getVendorFromResponse
     * @covers ::createGetVendorQuery
     */
    public function testGetVendor(): void
    {
        $vendorId = $this->expectedVendor['publicId'];
        $response = $this->expectedVendorResponse;
        $query = $this->financeCoreApi->createGetVendorQuery($vendorId);

        $expectedBody = [
            'query' => $query
        ];

        $this->brighteApi->expects(self::once())->method('cachedPost')
            ->with('getVendor', [$vendorId], self::PATH, json_encode($expectedBody))
            ->willReturn(json_decode(json_encode($response)));
        $vendor = $this->financeCoreApi->getVendor($vendorId);
        self::assertInstanceOf(FinanceCoreVendor::class, $vendor);
        self::assertEquals($this->expectedVendor, (array)$vendor);
    }
}
