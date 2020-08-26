<?php

namespace BrighteCapital\Api\Promotion\Exceptions;

use Throwable;

class RecordNotFoundException extends \Exception
{
    public function __construct($message = 'Record not found', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
