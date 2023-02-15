<?php

declare(strict_types=1);

namespace BrighteCapital\Api;

use BrighteCapital\Api\Models\FinanceCore\Account;
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
        $queryParameter = "publicId: \"{$vendorId}\"";
        $requestBody = [
            'query' => $this->createGetVendorQuery($queryParameter),
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

    public function getVendorByLegacyId(
        int $vendorLegacyId
    ): ?FinanceCoreVendor {
        $queryParameter = "legacyId: {$vendorLegacyId}";
        $requestBody = [
            'query' => $this->createGetVendorQuery($queryParameter),
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
        string $queryParameter
    ): string {
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
                rebateType
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
            $vendor->activeRebate->rebateType = $data->activeRebate->rebateType;
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
        string $financialProductId,
        string $vendorId = null,
        int $version = null
    ): string {
        $queryParameter = "financialProductId: \"{$financialProductId}\"";
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

    public function getFinancialProduct(string $id): ?FinancialProduct
    {
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
                      version
                    }
                    categoryGroup
                    fpAccountType
                    fpBranch
                }
            }
GQL;

        $requestBody = [
            'query' => $query,
            'variables' => [
                'id' => $id
            ]
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
        $product->id = $data->id;
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

    public function getFinanceAccount(string $id): ?Account
    {
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
        $data = $responseBody->data->financeAccount;
        
        return $this->getFinanceAccountFromResponse($data);
    }

    private function getFinanceAccountFromResponse($data): Account
    {
        $account = new Account();
        $account->id = $data->id;
        $account->status = $data->status;
        if (count($data->rebates) > 0) {
            foreach ($data->rebates as $rebate) {
                $rebateData = new VendorRebate();
                $rebateData->startDate = $rebate->startDate;
                $rebateData->finishDate = $rebate->finishDate;
                $rebateData->dollar = $rebate->dollar;
                $rebateData->percentage = $rebate->percentage;
                $rebateData->rebateType = $rebate->rebateType;
                $account->rebates[] = $rebateData;
            }
        }
        
        return $account;
    }
}
