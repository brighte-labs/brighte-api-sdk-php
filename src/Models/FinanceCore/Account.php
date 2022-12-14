<?php

declare(strict_types=1);

namespace BrighteCapital\Api\Models\FinanceCore;

class Account
{

    /** @var string Account ID */
    public $id;

    /** @var string Status */
    public $status;

    /** @var VendorRebate[] Rebates */
    public $rebates;
}
