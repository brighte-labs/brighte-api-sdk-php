<?php

declare(strict_types=1);

namespace BrighteCapital\Api\Models\FinanceCore;

class Vendor
{

    /** @var int Entity ID */
    public $legacyId;

    /** @var string Remote ID */
    public $publicId;

    /** @var string Trading Name */
    public $tradingName;

    /** @var string Salesforce Account ID */
    public $sfAccountId;

    /** @var string Slug */
    public $slug;

    /** @var VendorRebate Active Rebate */
    public $activeRebate = null;

    /** @var ApprovedFinancialProduct[] Approved Financial Product */
    public $approvedFinancialProducts = null;
}
