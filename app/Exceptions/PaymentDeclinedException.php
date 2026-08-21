<?php

namespace App\Exceptions;

use Exception;

class PaymentDeclinedException extends Exception
{
    public function __construct(public readonly string $errorCode, string $message)
    {
        parent::__construct($message);
    }
}
