<?php

declare(strict_types=1);

namespace BrighteCapital\Api\Models;

class ProductConfig
{
    /** @var int version */
    public $version;

    /** @var string finpower account type */
    public $fpAccountType;
    
    /** @var string finpower branch */
    public $fpBranch;

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
    public $minimumFinanceAmount;

    /** @var float maximum finance amount */
    public $maximumFinanceAmount;

    /** @var int min repayment year */
    public $minRepaymentYear;

    /** @var int max repayment year */
    public $maxRepaymentYear;

    /** @var bool force CCA process */
    public $forceCcaProcess;

    /** @var string payment cycle */
    public $paymentCycle;

    /** @var bool invoice required */
    public $invoiceRequired;
    
    /** @var bool manual settlement required */
    public $manualSettlementRequired;
}
