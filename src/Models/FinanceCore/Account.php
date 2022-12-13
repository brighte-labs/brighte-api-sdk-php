<?php

declare(strict_types=1);

namespace BrighteCapital\Api\Models\FinanceCore;

class Account
{

    /** @var string Account ID */
    public $id;

    /** @var int Loan Type Id */
    public $loanTypeId;

    /** @var int Vendor Id */
    public $vendorId;

    /** @var string Status */
    public $status;

    /** @var VendorRebate[] Rebates */
    public $rebates;
}
