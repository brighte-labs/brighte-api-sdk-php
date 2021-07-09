<?php

declare(strict_types=1);

namespace BrighteCapital\Api\Models;

class Vendor
{

    /** @var int Entity ID */
    public $id;

    /** @var string Remote ID */
    public $remoteId;

    /** @var string Trading Name */
    public $tradingName;

    /** @var string Salesforce Account ID */
    public $salesforceAccountId;

    /** @var string Accounts Email Address */
    public $accountsEmail;

    /** @var string Slug */
    public $slug;

    /** @var VendorFlag[] */
    public $flags;
}
