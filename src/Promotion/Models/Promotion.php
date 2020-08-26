<?php

namespace BrighteCapital\Api\Promotion\Models;

class Promotion
{
    /** @var int */
    public $id;

    /** @var string */
    public $code;

    /** @var array */
    public $products;

    /** @var int */
    public $type_id;

    /** @var string */
    public $description;

    /** @var string */
    public $contents;

    /** @var string */
    public $display_title;

    /** @var string */
    public $display_text;

    /** @var \DateTime */
    public $start_date;

    /** @var \DateTime */
    public $end_date;
}
