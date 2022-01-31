<?php

declare(strict_types=1);

namespace BrighteCapital\Api\Tests\Pact;

use BrighteCapital\Api\FinanceCoreApi;
use BrighteCapital\Api\Models\FinancialProductConfig;
use BrighteCapital\Api\Models\FinancialProduct;
use PhpPact\Consumer\InteractionBuilder;
use PhpPact\Consumer\Matcher\Matcher;
use PhpPact\Consumer\Model\ConsumerRequest;
use PhpPact\Consumer\Model\ProviderResponse;
use PhpPact\Standalone\MockService\MockServerEnvConfig;
use Psr\Log\LoggerInterface;
use BrighteCapital\Api\BrighteApi;
use Psr\Cache\CacheItemPoolInterface;
use stdClass;

class FinanceCorePactTest extends \PHPUnit\Framework\TestCase
{
    public const REQUEST_CONTENT_TYPE = 'application/json';
    public const RESPONSE_CONTENT_TYPE = 'application/json; charset=utf-8';


    protected $logger;

    protected $brighteApi;

    /** @var \BrighteCapital\Api\FinanceCoreApi */
    protected $financeCoreApi;

    protected $builder;

    protected $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->http = new PactTestingHttpClient();
        $this->cache = $this->createMock(CacheItemPoolInterface::class);

        $this->brighteApi = $this->getMockBuilder(BrighteApi::class)
            ->setConstructorArgs([
                    $this->http,
                    $this->logger,
                    ['uri' => 'http://localhost:7200/'],
                    $this->cache,
                ])
            ->setMethods(['getToken'])
            ->getMock();
        
        $this->brighteApi->method('getToken')->withAnyParameters()
            ->willReturn('test-token');

        $this->financeCoreApi = new FinanceCoreApi($this->logger, $this->brighteApi);
        $this->builder = new InteractionBuilder(new MockServerEnvConfig());

        $this->config = new FinancialProductConfig();
        $this->config->version = 1;
        $this->config->establishmentFee = 4.98;
        $this->config->interestRate = 5.98;
        $this->config->applicationFee = 6.98;
        $this->config->annualFee = 7.98;
        $this->config->weeklyAccountFee = 8.98;
        $this->config->latePaymentFee = 9.98;
        $this->config->introducerFee = 10.98;
        $this->config->enableExpressSettlement = true;
        $this->config->minFinanceAmount = 11.98;
        $this->config->maxFinanceAmount = 12.98;
        $this->config->minRepaymentMonth = 13;
        $this->config->maxRepaymentMonth = 30;
        $this->config->forceCcaProcess = true;
        $this->config->defaultPaymentCycle = 'weekly';
        $this->config->invoiceRequired = true;
        $this->config->manualSettlementRequired = true;
    }

    /**
     *
     * @throws \Exception
     */
    public function testGetFinancialProductConfig()
    {
        $matcher = new Matcher();
        $request = new ConsumerRequest();

        $body = new \stdClass();
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
                    version
                }
            }
GQL;
        $body->query = $matcher->like($query);
        $request
            ->setMethod('POST')
            ->setPath('/graphql')
            ->setBody($body)
            ->addHeader('Content-Type', self::REQUEST_CONTENT_TYPE);

        $body = new \stdClass();
        $body->data = new \stdClass();
        $body->data->financialProductConfiguration = $matcher->like($this->config);

        $response = new ProviderResponse();
        $response
            ->setStatus(200)
            ->addHeader('Content-Type', self::RESPONSE_CONTENT_TYPE)
            ->setBody($body);

        $this->builder
            ->uponReceiving('A request to get financial product configuration')
            ->with($request)
            ->willRespondWith($response);

        $this->financeCoreApi->getFinancialProductConfig('slug');

        $hasException = false;
        try {
            $this->builder->verify();
        } catch (\Exception $e) {
            $hasException = true;
            echo $e->getMessage();
        }

        $this->assertFalse($hasException, "We expect the pacts to validate");
    }

    /**
     *
     * @throws \Exception
     */
    public function testGetFinancialProduct()
    {
        $matcher = new Matcher();
        $request = new ConsumerRequest();

        $body = new \stdClass();
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
        $body->query = $matcher->like($query);
        $request
            ->setMethod('POST')
            ->setPath('/graphql')
            ->setBody($body)
            ->addHeader('Content-Type', self::REQUEST_CONTENT_TYPE);

        $product = new FinancialProduct();
        $product->slug = 'GreenLoan';
        $product->name = 'test-name';
        $product->type = 'Loan';
        $product->customerType = 'Residential';
        $product->loanTypeId = 1;
        $product->configuration = $this->config;
        $product->categoryGroup = 'Green';
        $product->fpAccountType = 'test-account-type';
        $product->fpBranch = 'test-branch';

        $body = new \stdClass();
        $body->data = new \stdClass();
        $body->data->financialProduct = $matcher->like($product);

        $response = new ProviderResponse();
        $response
            ->setStatus(200)
            ->addHeader('Content-Type', self::RESPONSE_CONTENT_TYPE)
            ->setBody($body);

        $this->builder
            ->uponReceiving('A request to get financial product')
            ->with($request)
            ->willRespondWith($response);

        $this->financeCoreApi->getFinancialProduct('slug');

        $hasException = false;
        try {
            $this->builder->verify();
        } catch (\Exception $e) {
            $hasException = true;
            echo $e->getMessage();
        }

        $this->assertFalse($hasException, "We expect the pacts to validate");
    }

    protected function tearDown()
    {
        $this->builder->finalize();
    }
}
