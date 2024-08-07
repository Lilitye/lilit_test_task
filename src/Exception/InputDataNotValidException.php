<?php

namespace App\Exception;

use Exception;
use Throwable;

class InputDataNotValidException extends Exception implements Throwable
{
    private array $errorMessages;

    public function __construct(array $errorMessages, $message = "", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->errorMessages = $errorMessages;
    }

    public function getErrors(): array
    {
        return $this->errorMessages;
    }
}