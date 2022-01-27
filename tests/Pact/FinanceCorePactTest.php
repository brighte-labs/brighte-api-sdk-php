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

class FinanceCorePactTest extends \PHPUnit\Framework\TestCase
{
    public const CONTENT_TYPE = 'application/json';

    protected $logger;

    protected $brighteApi;

    /** @var \BrighteCapital\Api\FinanceCoreApi */
    protected $financeCoreApi;

    protected $builder;

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

        $config = new MockServerEnvConfig();
        $this->builder = new InteractionBuilder($config);
    }

    /**
     *
     * @throws \Exception
     */
    public function testGetFinancialProductConfig()
    {
        $matcher = new Matcher();
        $request = new ConsumerRequest();
        $request
            ->setMethod('POST')
            ->setPath('/v2/finance/graphql')
            ->addHeader('Content-Type', self::CONTENT_TYPE);

        $config = new FinancialProductConfig();
        $config->version = 1;
        $config->establishmentFee = 4.98;
        $config->interestRate = 5.98;
        $config->applicationFee = 6.98;
        $config->annualFee = 7.98;
        $config->weeklyAccountFee = 8.98;
        $config->latePaymentFee = 9.98;
        $config->introducerFee = 10.98;
        $config->enableExpressSettlement = true;
        $config->minFinanceAmount = 11.98;
        $config->maxFinanceAmount = 12.98;
        $config->minRepaymentMonth = 13;
        $config->maxRepaymentMonth = 30;
        $config->forceCcaProcess = true;
        $config->defaultPaymentCycle = 'weekly';
        $config->invoiceRequired = true;
        $config->manualSettlementRequired = true;

        $body = new \stdClass();
        $body->data = new \stdClass();
        $body->data->financialProductConfiguration = $matcher->like($config);

        $response = new ProviderResponse();
        $response
            ->setStatus(200)
            ->addHeader('Content-Type', self::CONTENT_TYPE)
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
        $request
            ->setMethod('POST')
            ->setPath('/v2/finance/graphql')
            ->addHeader('Content-Type', self::CONTENT_TYPE);

        $config = new FinancialProductConfig();
        $config->version = 1;
        $config->establishmentFee = 4.98;
        $config->interestRate = 5.98;
        $config->applicationFee = 6.98;
        $config->annualFee = 7.98;
        $config->weeklyAccountFee = 8.98;
        $config->latePaymentFee = 9.98;
        $config->introducerFee = 10.98;
        $config->enableExpressSettlement = true;
        $config->minFinanceAmount = 11.98;
        $config->maxFinanceAmount = 12.98;
        $config->minRepaymentMonth = 13;
        $config->maxRepaymentMonth = 30;
        $config->forceCcaProcess = true;
        $config->defaultPaymentCycle = 'weekly';
        $config->invoiceRequired = true;
        $config->manualSettlementRequired = true;

        $product = new FinancialProduct();
        $product->slug = 'GreenLoan';
        $product->name = 'test-name';
        $product->type = 'Loan';
        $product->customerType = 'Residential';
        $product->loanTypeId = 1;
        $product->configuration = $config;
        $product->categoryGroup = 'Green';
        $product->fpAccountType = 'test-account-type';
        $product->fpBranch = 'test-branch';

        $body = new \stdClass();
        $body->data = new \stdClass();
        $body->data->financialProduct = $matcher->like($product);

        $response = new ProviderResponse();
        $response
            ->setStatus(200)
            ->addHeader('Content-Type', self::CONTENT_TYPE)
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
