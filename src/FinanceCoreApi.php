<?php

declare(strict_types=1);

namespace BrighteCapital\Api;

use BrighteCapital\Api\Models\FinanceCore\Vendor as FinanceCoreVendor;
use BrighteCapital\Api\Models\FinanceCore\VendorRebate;
use BrighteCapital\Api\Models\FinancialProductConfig;
use BrighteCapital\Api\Models\FinancialProduct;

class FinanceCoreApi extends \BrighteCapital\Api\AbstractApi
{
    public const PATH = '/../v2/finance/graphql';

    public function getVendor(
        string $vendorId
    ): ?FinanceCoreVendor {
        $requestBody = [
            'query' => $this->createGetVendorQuery($vendorId),
        ];

        $responseBody = $this->brighteApi->cachedPost(
            __FUNCTION__,
            func_get_args(),
            self::PATH,
            json_encode($requestBody),
            '',
            [],
            true
        );

        if ($responseBody == null) {
            return null;
        }
        $data = $responseBody->data->vendor;

        return $this->getVendorFromResponse($data);
    }

    public function createGetVendorQuery(
        string $vendorId
    ): string {
        $queryParameter = "publicId: \"{$vendorId}\"";

        return <<<GQL
        query {
            vendor (filter: { $queryParameter }) {
              legacyId
              publicId
              tradingName
              sfAccountId
              slug
              activeRebate {
                startDate
                finishDate
                dollar
                percentage
              }
            }
          }
GQL;
    }

    private function getVendorFromResponse($data): FinanceCoreVendor
    {
        $vendor = new FinanceCoreVendor();
        $vendor->legacyId = $data->legacyId;
        $vendor->publicId = $data->publicId;
        $vendor->tradingName = $data->tradingName;
        $vendor->sfAccountId = $data->sfAccountId;
        $vendor->slug = $data->slug;
        if ($data->activeRebate !== null) {
            $vendor->activeRebate = new VendorRebate();
            $vendor->activeRebate->startDate = $data->activeRebate->startDate;
            $vendor->activeRebate->finishDate = $data->activeRebate->finishDate;
            $vendor->activeRebate->dollar = $data->activeRebate->dollar;
            $vendor->activeRebate->percentage = $data->activeRebate->percentage;
        }
        
        return $vendor;
    }

    public function getFinancialProductConfig(
        string $slug,
        string $vendorId = null,
        int $version = null
    ): ?FinancialProductConfig {
        $requestBody = [
            'query' => $this->createGetFinancialProductConfigQuery($slug, $vendorId, $version),
        ];

        $responseBody = $this->brighteApi->cachedPost(
            __FUNCTION__,
            func_get_args(),
            self::PATH,
            json_encode($requestBody),
            '',
            [],
            true
        );

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
    
        $responseBody = $this->brighteApi->cachedPost(
            __FUNCTION__,
            func_get_args(),
            self::PATH,
            json_encode($requestBody),
            '',
            [],
            true
        );

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
}
