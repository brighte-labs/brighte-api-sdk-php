<?php

declare(strict_types=1);

namespace BrighteCapital\Api\Models;

class PromoCode
{
    /** @var int Entity ID */
    public $id;

    /** @var string code */
    public $code;

    /** @var string type */
    public $type;

    /** @var string start */
    public $start;

    /** @var string end */
    public $end;
}
