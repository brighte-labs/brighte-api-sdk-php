<?php

declare(strict_types=1);

namespace BrighteCapital\Api\Models;

class Manufacturer
{

    /** @var int Entity ID */
    public $id;

    /** @var string Name */
    public $name;

    /** @var string Description */
    public $description;

    /** @var string Icon */
    public $icon;

    /** @var string Slug */
    public $slug;

    /** @var boolean Premium */
    public $premium;
}
