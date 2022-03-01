<?php

declare(strict_types=1);

namespace BrighteCapital\Api;

use BrighteCapital\Api\Models\FinancialProductConfig;
use BrighteCapital\Api\Models\FinancialProduct;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;

class FinanceCoreApi extends \BrighteCapital\Api\AbstractApi
{
    public const PATH = '/../v2/finance';
    public const ERROR_FIELD_NAME_IN_JSON = 'errors';

    public function getFinancialProductConfig(
        string $slug,
        string $vendorId = null,
        int $version = null
    ): ?FinancialProductConfig {
        $body = [
            'query' => $this->createGetFinancialProductConfigQuery($slug, $vendorId, $version),
        ];

        $response = $this->brighteApi->post(sprintf('%s/graphql', self::PATH), json_encode($body), '', [], true);

        $body = $this->checkIfContainsError(__FUNCTION__, $response);
        if ($body === null) {
            return null;
        }

        $data = $body->data->financialProductConfiguration;

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

        $body = [
            'query' => $query
        ];
    
        $response = $this->brighteApi->post(sprintf('%s/graphql', self::PATH), json_encode($body), '', [], true);

        $body = $this->checkIfContainsError(__FUNCTION__, $response);
        if ($body === null) {
            return null;
        }

        $data = $body->data->financialProduct;
        
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
}
