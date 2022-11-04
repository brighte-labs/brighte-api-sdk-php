<?php

declare(strict_types=1);

namespace BrighteCapital\Api\Models\FinanceCore;

class VendorRebate
{

    /** @var \DateTime Start Date */
    public $startDate;

    /** @var \DateTime Finish Date */
    public $finishDate;

    /** @var float Dollar */
    public $dollar;

    /** @var float Percentage */
    public $percentage;

    /** @var string Rebate Type */
    public $rebateType;
}
