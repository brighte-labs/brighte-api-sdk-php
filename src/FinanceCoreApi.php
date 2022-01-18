<?php

declare(strict_types=1);

namespace BrighteCapital\Api;

use BrighteCapital\Api\Models\ProductConfig;
use Fig\Http\Message\StatusCodeInterface;

class FinanceCoreApi extends \BrighteCapital\Api\AbstractApi
{
    public const PATH = '../v2/finance';

    public function getProductConfig(string $slug, string $vendorId = null, int $version = null): ?ProductConfig
    {
        $query = <<<GQL
            query {
                getProductConfiguration(
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
                    minimumFinanceAmount
                    maximumFinanceAmount
                    minRepaymentYear
                    maxRepaymentYear
                    forceCcaProcess
                    defaultPaymentCycle
                    invoiceRequired
                    manualSettlementRequired
                    fpBranch
                    fpAccountType
                }
            }
GQL;

        $body = [
            'query' => $query
        ];
    
        $response = $this->brighteApi->post(sprintf('%s/graphql', self::PATH), json_encode($body), '', [
            'Content-Type' => 'application/json'
        ], true);
        
        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            $this->logResponse(__FUNCTION__, $response);

            return null;
        }
        
        $json = $response->getBody()->getContents();
        $body = json_decode($json);
        $data = $body->data->getProductConfiguration;

        $config = new ProductConfig();
        $config->version = $data->version;
        $config->establishmentFee = $data->establishmentFee;
        $config->interestRate = $data->interestRate;
        $config->applicationFee = $data->applicationFee;
        $config->annualFee = $data->annualFee;
        $config->weeklyAccountFee = $data->weeklyAccountFee;
        $config->latePaymentFee = $data->latePaymentFee;
        $config->introducerFee = $data->introducerFee;
        $config->enableExpressSettlement = $data->enableExpressSettlement;
        $config->fpAccountType = $data->fpAccountType;
        $config->minimumFinanceAmount = $data->minimumFinanceAmount;
        $config->maximumFinanceAmount = $data->maximumFinanceAmount;
        $config->minRepaymentYear = $data->minRepaymentYear;
        $config->maxRepaymentYear = $data->maxRepaymentYear;
        $config->fpBranch = $data->fpBranch;
        $config->forceCcaProcess = $data->forceCcaProcess;
        $config->defaultPaymentCycle = $data->defaultPaymentCycle;
        $config->invoiceRequired = $data->invoiceRequired;
        $config->manualSettlementRequired = $data->manualSettlementRequired;

        return $config;
    }
}
