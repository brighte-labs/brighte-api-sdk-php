<?php

declare(strict_types=1);

namespace BrighteCapital\Api\Models;

class Notification
{

    /** @var string */
    public $id;

    /** @var string */
    public $recipient;

    /** @var string */
    public $templateKey;

    /** @var array */
    public $data = [];
}
