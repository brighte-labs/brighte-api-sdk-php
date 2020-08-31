<?php


namespace BrighteCapital\Api\Exceptions;


use Throwable;

class BadRequestException extends \Exception
{
    public $errors = [];

    public function __construct($errors, $message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

}
