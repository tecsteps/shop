<?php

namespace App\Exceptions;

use Exception;

class PaymentFailedException extends Exception
{
    public function __construct(public readonly string $errorCode, ?string $message = null)
    {
        parent::__construct($message ?? "Payment failed: {$errorCode}");
    }
}
