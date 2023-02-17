<?php

declare(strict_types=1);

namespace BrighteCapital\Api\Models;

class FinancialProductConfig
{
    /** @var int version */
    public $version;

    /** @var float establishment fee */
    public $establishmentFee;

    /** @var float interest rate */
    public $interestRate;

    /** @var float application fee */
    public $applicationFee;

    /** @var float annual fee */
    public $annualFee;

    /** @var float weekly account fee */
    public $weeklyAccountFee;

    /** @var float late payment fee */
    public $latePaymentFee;

    /** @var float introducer fee */
    public $introducerFee;

    /** @var bool enable express settlement */
    public $enableExpressSettlement;

    /** @var float minimum finance amount */
    public $minFinanceAmount;

    /** @var float maximum finance amount */
    public $maxFinanceAmount;

    /** @var int min repayment month */
    public $minRepaymentMonth;

    /** @var int max repayment month */
    public $maxRepaymentMonth;

    /** @var bool force CCA process */
    public $forceCcaProcess;

    /** @var string default payment cycle */
    public $defaultPaymentCycle;

    /** @var bool invoice required */
    public $invoiceRequired;
    
    /** @var bool manual settlement required */
    public $manualSettlementRequired;

    /** @var bool risk based pricing enabled */
    public $riskBasedPricing;
}
