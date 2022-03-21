<?php

declare(strict_types=1);

namespace BrighteCapital\Api;

use BrighteCapital\Api\Models\FinancialProductConfig;
use BrighteCapital\Api\Models\FinancialProduct;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Cache\Adapter\Common\CacheItem;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

class FinanceCoreApi extends \BrighteCapital\Api\AbstractApi
{
    public const PATH = '/../v2/finance';
    public const ERROR_FIELD_NAME_IN_JSON = 'errors';

    /** @var CacheItemPoolInterface|null */
    protected $cacheItemPool;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param \BrighteCapital\Api\BrighteApi $brighteApi
     * @param CacheItemPoolInterface $cache
     */
    public function __construct(LoggerInterface $logger, BrighteApi $brighteApi, CacheItemPoolInterface $cache)
    {
        parent::__construct($logger, $brighteApi);
        $this->cacheItemPool = $cache;
    }

    public function getFinancialProductConfig(
        string $slug,
        string $vendorId = null,
        int $version = null
    ): ?FinancialProductConfig {
        $requestBody = [
            'query' => $this->createGetFinancialProductConfigQuery($slug, $vendorId, $version),
        ];

        $responseBody = $this->getCached(__FUNCTION__, func_get_args(), $requestBody);
        if ($responseBody == null) {
            return null;
        }
        $data = $responseBody->data->financialProductConfiguration;

        return $this->getFinancialProductConfigFromResponse($data);
    }

    public function createGetFinancialProductConfigQuery(
        string $slug,
        string $vendorId = null,
        int $version = null
    ): string {
        $queryParameter = "slug: {$slug}";
        if ($vendorId) {
            $queryParameter .= PHP_EOL . "vendorId: \"{$vendorId}\"";
        }
        if ($version) {
            $queryParameter .= PHP_EOL . "version: {$version}";
        }

        return <<<GQL
            query {
                financialProductConfiguration(
                {$queryParameter}
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
    }

    public function getFinancialProduct(string $slug): ?FinancialProduct
    {
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

        $requestBody = [
            'query' => $query
        ];
    
        $responseBody = $this->getCached(__FUNCTION__, func_get_args(), $requestBody);
        if ($responseBody == null) {
            return null;
        }
        $data = $responseBody->data->financialProduct;
        
        $product = new FinancialProduct();
        $product->slug = $data->slug;
        $product->name = $data->name;
        $product->type = $data->type;
        $product->customerType = $data->customerType;
        $product->loanTypeId = $data->loanTypeId;
        $product->configuration = $this->getFinancialProductConfigFromResponse($data->configuration);
        $product->categoryGroup = $data->categoryGroup;
        $product->fpAccountType = $data->fpAccountType;
        $product->fpBranch = $data->fpBranch;

        return $product;
    }

    private function getFinancialProductConfigFromResponse($configuration): FinancialProductConfig
    {
        $config = new FinancialProductConfig();
        $config->version = $configuration->version;
        $config->establishmentFee = $configuration->establishmentFee;
        $config->interestRate = $configuration->interestRate;
        $config->applicationFee = $configuration->applicationFee;
        $config->annualFee = $configuration->annualFee;
        $config->weeklyAccountFee = $configuration->weeklyAccountFee;
        $config->latePaymentFee = $configuration->latePaymentFee;
        $config->introducerFee = $configuration->introducerFee;
        $config->enableExpressSettlement = $configuration->enableExpressSettlement;
        $config->minFinanceAmount = $configuration->minFinanceAmount;
        $config->maxFinanceAmount = $configuration->maxFinanceAmount;
        $config->minRepaymentMonth = $configuration->minRepaymentMonth;
        $config->maxRepaymentMonth = $configuration->maxRepaymentMonth;
        $config->forceCcaProcess = $configuration->forceCcaProcess;
        $config->defaultPaymentCycle = $configuration->defaultPaymentCycle;
        $config->invoiceRequired = $configuration->invoiceRequired;
        $config->manualSettlementRequired = $configuration->manualSettlementRequired;
        return $config;
    }

    private function checkIfContainsError(string $function, ResponseInterface $response)
    {
        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            $this->logGraphqlResponse($function, $response);

            return null;
        }

        $json = $response->getBody()->getContents();
        $body = json_decode($json);

        if (property_exists($body, self::ERROR_FIELD_NAME_IN_JSON)) {
            $this->logGraphqlResponse($function, $response);

            return null;
        }
        return $body;
    }

    private function getCached(string $functionName, array $parameters, array $body)
    {
        $key = implode('_', [$functionName, implode('_', $parameters)]);
        if ($cachedItem = $this->cacheItemPool->getItem($key)) {
            return $cachedItem->get();
        }

        $response = $this->brighteApi->post(sprintf('%s/graphql', self::PATH), json_encode($body), '', [], true);

        $responseBody = $this->checkIfContainsError($functionName, $response);
        if ($responseBody === null) {
            return null;
        }

        $item = new CacheItem($key, true, $responseBody);
        $expires = new \DateInterval('PT' . strtoupper("15m"));
        $item->expiresAfter($expires);
        $this->cacheItemPool->save($item);

        return $responseBody;
    }
}
