<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidDiscountException extends RuntimeException
{
    public function __construct(public string $errorCode, string $message)
    {
        parent::__construct($message);
    }
}
