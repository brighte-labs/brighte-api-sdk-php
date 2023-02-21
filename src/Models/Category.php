<?php

declare(strict_types=1);

namespace BrighteCapital\Api\Models;

class Category
{
    /** @var int Entity ID */
    public $id;

    /** @var string Name */
    public $name;

    /** @var string Slug */
    public $slug;

    /** @var string Group */
    public $group;
}
