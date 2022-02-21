<?php

declare(strict_types=1);

namespace BrighteCapital\Api;

use BrighteCapital\Api\Models\FinancialProductConfig;
use BrighteCapital\Api\Models\FinancialProduct;
use Fig\Http\Message\StatusCodeInterface;

class FinanceCoreApi extends \BrighteCapital\Api\AbstractApi
{
    public const PATH = '/../v2/finance';

    public function getFinancialProductConfig(
        string $slug,
        string $vendorId = null,
        int $version = null
    ): ?FinancialProductConfig {
        $body = [
            'query' => $this->createGetFinancialProductConfigQuery($slug, $vendorId, $version),
        ];

        $response = $this->brighteApi->post(sprintf('%s/graphql', self::PATH), json_encode($body), '', [], true);

        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            $this->logResponse(__FUNCTION__, $response);

            return null;
        }

        $json = $response->getBody()->getContents();
        $body = json_decode($json);
        $data = $body->data->financialProductConfiguration;

        return $this->getFinancialProductConfigFromResponse($data);
    }

    public function createGetFinancialProductConfigQuery(
        string $slug,
        string $vendorId = null,
        int $version = null
    ): string {
        $queryParameter = "slug: {$slug}" . PHP_EOL;
        if ($vendorId) {
            $queryParameter .= "vendorId: \"{$vendorId}\"" . PHP_EOL;
        }
        if ($version) {
            $queryParameter .= "version: {$version}" . PHP_EOL;
        }
        $query = <<<GQL
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
        return $query;
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
        
        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            $this->logResponse(__FUNCTION__, $response);

            return null;
        }
        
        $json = $response->getBody()->getContents();
        $body = json_decode($json);
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
}
