<?php

declare(strict_types=1);

namespace BrighteCapital\Tests\Api;

use BrighteCapital\Api\BrighteApi;
use BrighteCapital\Api\Models\FinancialProductConfig;
use BrighteCapital\Api\Models\FinancialProduct;
use BrighteCapital\Api\FinanceCoreApi;
use BrighteCapital\Api\Models\FinanceCore\Account;
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

    private $expectedFinanceAccount;

    private $expectedFinanceAccountResponse;

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
            'riskBasedPricing' => true,
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

        $this->expectedFinanceAccount = [
            'id' => '1234',
            'status' => 'PENDING',
            'rebates' => [
                [
                    'startDate' => '2022-10-04T23:22:34.000Z',
                    'finishDate' => '2025-10-04T23:22:34.000Z',
                    'dollar' => 500,
                    'percentage' => null,
                    'rebateType' => 'PRICE'
                ]
            ]
        ];

        $this->expectedFinanceAccountResponse = [
            'data' => [
                'financeAccount' => $this->expectedFinanceAccount,
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
                ['brighte-green-loan', null, null, null],
                $this->expectedConfigResponse,
            ],
            [
                ['brighte-green-loan', 'test-vendor-id', null, null],
                $this->expectedConfigResponse,
            ],
            [
                ['brighte-green-loan', null, 1, null],
                $this->expectedConfigResponse,
            ],
            [
                ['brighte-green-loan', 'test-vendor-id', 1, null],
                $this->expectedConfigResponse,
            ],
            [
                ['brighte-green-loan', null, null, 'test-promo-code'],
                $this->expectedConfigResponse,
            ]
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::getFinancialProductConfig
     * @covers ::getFinancialProductConfigFromResponse
     * @dataProvider financialProductConfigProvider
     */
    public function testGetFinancialProductConfig($input, $response): void
    {
        $slug = $input[0];
        $vendorId = $input[1];
        $version = $input[2];
        $promoCode = $input[3];

        $query = <<<GQL
        query FinancialProductConfiguration(
            \$financialProductId: String, 
            \$version: Int, 
            \$vendorId: String, 
            \$promoCode: String) {
            financialProductConfiguration(
            financialProductId: \$financialProductId,
            version: \$version,
            vendorId: \$vendorId,
            promoCode: \$promoCode
            ) {
            establishmentFee
            interestRate
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
            riskBasedPricing
            version
            }
        }
GQL;

        $expectedBody = [
            'query' => $query,
            'variables' => [
                "financialProductId" => $slug,
                "vendorId" => $vendorId,
                "version" => $version,
                "promoCode" => $promoCode,
            ],
        ];

        $this->brighteApi->expects(self::once())->method('cachedPost')
            ->with('getFinancialProductConfig', $input, self::PATH, json_encode($expectedBody))
            ->willReturn(json_decode(json_encode($response)));
        $config = $this->financeCoreApi->getFinancialProductConfig($slug, $vendorId, $version, $promoCode);
        self::assertInstanceOf(FinancialProductConfig::class, $config);
        self::assertEquals($this->expectedConfig, (array)$config);
    }

    /**
     * @covers ::__construct
     * @covers ::getFinancialProductConfig
     * @covers ::getFinancialProductConfigFromResponse
     */
    public function testGetFinancialProductConfigWhenPromotionDoesNotExists(): void
    {
        $slug = 'brighte-green-loan';
        $vendorId = null;
        $version = null;
        $promoCode = 'non-existent-promo-code';

        $input = [
            $slug = 'brighte-green-loan', $vendorId = null, $version = null, $promoCode = 'non-existent-promo-code'
        ];

        $query = <<<GQL
        query FinancialProductConfiguration(
            \$financialProductId: String, 
            \$version: Int, 
            \$vendorId: String, 
            \$promoCode: String) {
            financialProductConfiguration(
            financialProductId: \$financialProductId,
            version: \$version,
            vendorId: \$vendorId,
            promoCode: \$promoCode
            ) {
            establishmentFee
            interestRate
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
            riskBasedPricing
            version
            }
        }
GQL;

        $expectedBody = [
            'query' => $query,
            'variables' => [
                "financialProductId" => $slug,
                "vendorId" => $vendorId,
                "version" => $version,
                "promoCode" => $promoCode,
            ],
        ];

        $expectedResponse = null;

        $this->brighteApi->expects(self::once())->method('cachedPost')
            ->with('getFinancialProductConfig', $input, self::PATH, json_encode($expectedBody))
            ->willReturn(json_decode(json_encode($expectedResponse)));
        $config = $this->financeCoreApi->getFinancialProductConfig($slug, $vendorId, $version, $promoCode);
        self::assertNull($config);
    }

    /**
     * @covers ::__construct
     * @covers ::getFinancialProduct
     * @covers ::getFinancialProductConfigFromResponse
     */
    public function testGetFinancialProduct(): void
    {
        $response = [
            'data' => [
                'financialProduct' => [
                    'id' => 'brighte-green-loan',
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

        $financialProductId = 'brighte-green-loan';

        $query = <<<GQL
            query FinancialProduct(\$id: String!) {
                financialProduct(
                id: \$id
                ) {
                    id
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
                      riskBasedPricing
                      version
                    }
                    categoryGroup
                    fpAccountType
                    fpBranch
                }
            }
GQL;

        $expectedBody = [
            'query' => $query,
            'variables' => ["id" => "brighte-green-loan"],
        ];

        $this->brighteApi->expects(self::once())->method('cachedPost')
            ->with('getFinancialProduct', [$financialProductId], self::PATH, json_encode($expectedBody))
            ->willReturn(json_decode(json_encode($response)));
        $product = $this->financeCoreApi->getFinancialProduct($financialProductId);
        $config = $product->configuration;
        self::assertInstanceOf(FinancialProduct::class, $product);
        self::assertEquals('brighte-green-loan', $product->id);
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
        $queryParameter = "publicId: \"{$vendorId}\"";
        $query = $this->financeCoreApi->createGetVendorQuery($queryParameter);

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

    /**
     * @covers ::__construct
     * @covers ::getVendor
     * @covers ::getVendorFromResponse
     * @covers ::createGetVendorQuery
     */
    public function testGetVendorWhenReturnsNull(): void
    {
        $vendorId = $this->expectedVendor['publicId'];
        $this->brighteApi
            ->expects(self::once())->method('cachedPost')
            ->willReturn(null);
        $vendor = $this->financeCoreApi->getVendor($vendorId);
        self::assertNull($vendor);
    }

    /**
     * @covers ::__construct
     * @covers ::getVendorByLegacyId
     * @covers ::getVendorFromResponse
     * @covers ::createGetVendorQuery
     */
    public function testGetVendorByLegacyId(): void
    {
        $vendorLegacyId = $this->expectedVendor['legacyId'];
        $response = $this->expectedVendorResponse;
        $queryParameter = "legacyId: {$vendorLegacyId}";
        $query = $this->financeCoreApi->createGetVendorQuery($queryParameter);

        $expectedBody = [
            'query' => $query
        ];

        $this->brighteApi->expects(self::once())->method('cachedPost')
            ->with('getVendorByLegacyId', [$vendorLegacyId], self::PATH, json_encode($expectedBody))
            ->willReturn(json_decode(json_encode($response)));
        $vendor = $this->financeCoreApi->getVendorByLegacyId($vendorLegacyId);
        self::assertInstanceOf(FinanceCoreVendor::class, $vendor);
        self::assertEquals($this->expectedVendor, (array)$vendor);
    }

    /**
     * @covers ::__construct
     * @covers ::getVendorByLegacyId
     * @covers ::getVendorFromResponse
     * @covers ::createGetVendorQuery
     */
    public function testGetVendorByLegacyIdWhenReturnsNull(): void
    {
        $legacyId = $this->expectedVendor['legacyId'];
        $this->brighteApi
            ->expects(self::once())->method('cachedPost')
            ->willReturn(null);
        $vendor = $this->financeCoreApi->getVendorByLegacyId($legacyId);
        self::assertNull($vendor);
    }

    /**
     * @covers ::__construct
     * @covers ::getFinanceAccount
     * @covers ::getFinanceAccountFromResponse
     */
    public function testGetFinanceAccount(): void
    {
        $id = $this->expectedFinanceAccount['id'];
        $query = <<<GQL
            query {
                financeAccount(
                id: "{$id}"
                ) {
                    id
                    status
                    rebates {
                        startDate
                        finishDate
                        dollar
                        percentage
                        rebateType
                    }
                }
            }
GQL;

        $expectedBody = [
            'query' => $query
        ];

        $expectedResponse = json_decode(json_encode($this->expectedFinanceAccountResponse));
        $this->brighteApi->expects(self::once())->method('cachedPost')
            ->with('getFinanceAccount', [$id], self::PATH, json_encode($expectedBody))
            ->willReturn($expectedResponse);
        $account = $this->financeCoreApi->getFinanceAccount($id);
        self::assertInstanceOf(Account::class, $account);
        self::assertInstanceOf(VendorRebate::class, $account->rebates[0]);
        self::assertEquals('PRICE', $account->rebates[0]->rebateType);
    }

    /**
     * @covers ::__construct
     * @covers ::getFinanceAccount
     * @covers ::getFinanceAccountFromResponse
     */
    public function testGetFinanceAccountWhenReturnsNull(): void
    {
        $id = $this->expectedFinanceAccount['id'];
        $this->brighteApi
            ->expects(self::once())->method('cachedPost')
            ->willReturn(null);
        $account = $this->financeCoreApi->getFinanceAccount($id);
        self::assertNull($account);
    }
}
