<?php


namespace BrighteCapital\Api\Exceptions;


class RecordNotFoundException
{
    public function __construct($message = 'Record not found', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

}
