<?php

declare(strict_types=1);

namespace BrighteCapital\Tests\Pact;

use BrighteCapital\Api\FinanceCoreApi;
use BrighteCapital\Api\Models\ProductConfig;
use PhpPact\Consumer\InteractionBuilder;
use PhpPact\Consumer\Matcher\Matcher;
use PhpPact\Consumer\Model\ConsumerRequest;
use PhpPact\Consumer\Model\ProviderResponse;
use PhpPact\Standalone\MockService\MockServerEnvConfig;
use Psr\Log\LoggerInterface;
use BrighteCapital\Api\BrighteApi;
use Psr\Cache\CacheItemPoolInterface;
use GuzzleHttp\Client;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

$dotenv = \Dotenv\Dotenv::createUnsafeImmutable(\dirname(\dirname(__DIR__)));
$dotenv->load();

class PactTestingHttpClient implements ClientInterface
{
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $client = new Client();
        $response = $client->send($request);
        return $response;
    }
}

class FinanceCoreApiTest extends \PHPUnit\Framework\TestCase
{
    protected $logger;

    protected $brighteApi;

    /** @var \BrighteCapital\Api\FinanceCoreApi */
    protected $financeCoreApi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->http = new PactTestingHttpClient();
        $this->cache = $this->createMock(CacheItemPoolInterface::class);
        $stub = $this->createPartialMock(BrighteApi::class, ['getToken']);

        $this->brighteApi = $this->getMockBuilder(BrighteApi::class)
            ->setConstructorArgs([
                    $this->http,
                    $this->logger,
                    ['uri' => 'http://localhost:7200/'],
                    $this->cache,
                ])
            ->enableProxyingToOriginalMethods()
            ->setMethods(['getToken'])
            ->setProxyTarget($stub)
            ->getMock();
        
        $stub->method('getToken')->withAnyParameters()
            ->willReturn('test-token');

        $this->financeCoreApi = new FinanceCoreApi($this->logger, $this->brighteApi);
    }

    /**
     *
     * @throws \Exception
     */
    public function testGetProductConfig()
    {
        $matcher = new Matcher();

        $request = new ConsumerRequest();
        $request
            ->setMethod('POST')
            ->setPath('/v2/finance/graphql')
            ->addHeader('Content-Type', 'application/json');

        $matcher = new Matcher();

        $config = new ProductConfig();
        $config->version = 1;
        $config->establishmentFee = 4.99;
        $config->interestRate = 5.99;
        $config->applicationFee = 6.99;
        $config->annualFee = 7.99;
        $config->weeklyAccountFee = 8.99;
        $config->latePaymentFee = 9.99;
        $config->introducerFee = 10.99;
        $config->enableExpressSettlement = true;
        $config->fpAccountType = 'savings';
        $config->minimumFinanceAmount = 11.99;
        $config->maximumFinanceAmount = 12.99;
        $config->minRepaymentYear = 13.99;
        $config->maxRepaymentYear = 14.99;
        $config->fpBranch = 'branch';
        $config->forceCcaProcess = true;
        $config->defaultPaymentCycle = 'weekly';
        $config->invoiceRequired = true;
        $config->manualSettlementRequired = true;

        $body = new \stdClass();
        $body->data = new \stdClass();
        $body->data->getProductConfiguration = $matcher->like($config);

        $response = new ProviderResponse();
        $response
            ->setStatus(200)
            ->addHeader('Content-Type', 'application/json')
            ->setBody($body);

        $config = new MockServerEnvConfig();
        $builder = new InteractionBuilder($config);
        $builder
            ->uponReceiving('A request to get product configuration')
            ->with($request)
            ->willRespondWith($response);

        $this->financeCoreApi->getProductConfig('slug');

        $hasException = false;
        try {
            $builder->verify();
        } catch (\Exception $e) {
            $hasException = true;
        }

        $this->assertFalse($hasException, "We expect the pacts to validate");
    }
}
