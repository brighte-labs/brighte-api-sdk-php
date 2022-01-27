<?php

declare(strict_types=1);

namespace BrighteCapital\Api\Models;

class FinancialProduct
{
    /** @var string slug */
    public $slug;

    /** @var string name */
    public $name;

    /** @var string product type */
    public $type;

    /** @var string customer type */
    public $customerType;

    /** @var int loan type id */
    public $loanTypeId;

    /** @var FinancialProductConfig configuration */
    public $configuration;

    /** @var string category group */
    public $categoryGroup;

    /** @var string finpower account type */
    public $fpAccountType;

    /** @var string finpower branch */
    public $fpBranch;
}
