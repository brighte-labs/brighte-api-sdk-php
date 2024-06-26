<?php

declare(strict_types=1);

namespace BrighteCapital\Api;

use BrighteCapital\Api\Models\Category;
use BrighteCapital\Api\Models\ClientDetail;
use BrighteCapital\Api\Models\FinanceCore\Account;
use BrighteCapital\Api\Models\FinanceCore\ApprovedFinancialProduct;
use BrighteCapital\Api\Models\FinanceCore\Vendor as FinanceCoreVendor;
use BrighteCapital\Api\Models\FinanceCore\VendorPromotion;
use BrighteCapital\Api\Models\FinanceCore\VendorRebate;
use BrighteCapital\Api\Models\FinancialProductConfig;
use BrighteCapital\Api\Models\FinancialProduct;
use BrighteCapital\Api\Models\User;
use Fig\Http\Message\StatusCodeInterface;

class FinanceCoreApi extends \BrighteCapital\Api\AbstractApi
{
    public const PATH = '/../v2/finance/graphql';

    public function getVendor(
        string $vendorId,
        bool $includeFinancialProduct = false
    ): ?FinanceCoreVendor {
        $queryParameter = "publicId: \"{$vendorId}\"";
        $requestBody = [
            'query' => $this->createGetVendorQuery($queryParameter, $includeFinancialProduct),
        ];

        $responseBody = $this->brighteApi->cachedPost(
            __FUNCTION__,
            func_get_args(),
            self::PATH,
            json_encode($requestBody),
            '',
            [],
            self::PATH,
            true
        );

        if ($responseBody == null) {
            return null;
        }
        $data = $responseBody->data->vendor;

        return $this->getVendorFromResponse($data);
    }

    public function getVendorByLegacyId(
        int $vendorLegacyId,
        bool $includeFinancialProduct = false
    ): ?FinanceCoreVendor {
        $queryParameter = "legacyId: {$vendorLegacyId}";
        $requestBody = [
            'query' => $this->createGetVendorQuery($queryParameter, $includeFinancialProduct),
        ];

        $responseBody = $this->brighteApi->cachedPost(
            __FUNCTION__,
            func_get_args(),
            self::PATH,
            json_encode($requestBody),
            '',
            [],
            self::PATH,
            true
        );

        if ($responseBody == null) {
            return null;
        }
        $data = $responseBody->data->vendor;

        return $this->getVendorFromResponse($data);
    }

    public function createGetVendorQuery(
        string $queryParameter,
        bool $includeFinancialProduct = false
    ): string {
        $approvedFinancialProducts = $includeFinancialProduct ? '
        approvedFinancialProducts {
            promotions {
                code
            }
            id
        }' : '';

        return <<<GQL
        query {
            vendor (filter: { $queryParameter }) {
              legacyId
              publicId
              tradingName
              sfAccountId
              slug
              $approvedFinancialProducts
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

        if (!empty($data->approvedFinancialProducts)) {
            foreach ($data->approvedFinancialProducts as $approvedFinancialProduct) {
                $approvedProduct = new ApprovedFinancialProduct();
                $approvedProduct->id = $approvedFinancialProduct->id;
                if (!empty($approvedFinancialProduct->promotions)) {
                    foreach ($approvedFinancialProduct->promotions as $promotion) {
                        $promo = new VendorPromotion();
                        $promo->code = $promotion->code;
                        $approvedProduct->promotions[] = $promo;
                    }
                }
                $vendor->approvedFinancialProducts[] = $approvedProduct;
            }
        }

        return $vendor;
    }

    public function getFinancialProductConfig(
        string $slug,
        string $vendorId = null,
        int $version = null,
        string $promoCode = null,
        string $category = null
    ): ?FinancialProductConfig {

        $query = <<<GQL
        query FinancialProductConfiguration(
            \$financialProductId: String, 
            \$version: Int, 
            \$vendorId: String, 
            \$promoCode: String,
            \$category: String) {
            financialProductConfiguration(
            financialProductId: \$financialProductId,
            version: \$version,
            vendorId: \$vendorId,
            promoCode: \$promoCode,
            category: \$category
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
            activeTo
            preventApplicationsAfterEndDate
            }
        }
GQL;

        $requestBody = [
            'query' => $query,
            'variables' => [
                'financialProductId' => $slug,
                'vendorId' => $vendorId,
                'version' => $version,
                'promoCode' => $promoCode,
                'category' => $category
            ]
        ];

        $responseBody = $this->brighteApi->cachedPost(
            __FUNCTION__,
            func_get_args(),
            self::PATH,
            json_encode($requestBody),
            '',
            [],
            self::PATH
        );

        if ($responseBody == null) {
            return null;
        }
        $data = $responseBody->data->financialProductConfiguration;

        return $this->getFinancialProductConfigFromResponse($data);
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
                      riskBasedPricing
                      version
                      activeTo
                      preventApplicationsAfterEndDate
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
            self::PATH
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
        $config->riskBasedPricing = $configuration->riskBasedPricing;
        $config->activeTo = ($configuration->activeTo ?? null) ? new \DateTime($configuration->activeTo) : null;
        $config->preventApplicationsAfterEndDate = (bool) ($configuration->preventApplicationsAfterEndDate ?? false);
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
            self::PATH
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

    /**
     * getCategoryById
     *
     * @param  int $categoryId
     * @return Category|null
     */
    public function getCategoryById(int $categoryId): ?Category
    {
        $query = <<<GQL
            query GetCategory(\$categoryId: Int) {
                category(id: \$categoryId) {
                    id
                    slug
                    name
                    group
                }
            }
GQL;
        $requestBody = [
            'query' => $query,
            'variables' => [
                'categoryId' => $categoryId
            ]
        ];

        $responseBody = $this->brighteApi->cachedPost(
            __FUNCTION__,
            func_get_args(),
            self::PATH,
            json_encode($requestBody),
            '',
            [],
            self::PATH
        );

        if ($responseBody == null) {
            return null;
        }

        $data = $responseBody->data->category;

        $category = new Category();
        $category->id = $data->id;
        $category->name = $data->name;
        $category->slug = $data->slug;
        $category->group = $data->group;

        return $category;
    }

    public function getClientDetails(string $clientId): ?ClientDetail
    {
        $path = "/../v2/finance";
        $url = sprintf('%s/lms/client/%s', $path, $clientId);
        $response = $this->brighteApi->get($url, '', [], self::PATH);

        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            return null;
        }

        $body = json_decode((string) $response->getBody());

        $clientDetail = new ClientDetail();
        $clientDetail->firstName = $body->firstName;
        $clientDetail->lastName = $body->lastName;
        $clientDetail->middleName = $body->middleName;
        $clientDetail->dateOfBirth = $body->dateOfBirth;

        return $clientDetail;
    }
}
