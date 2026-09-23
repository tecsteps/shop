<?php

namespace App\Exceptions;

use RuntimeException;

class PaymentDeclinedException extends RuntimeException
{
    public function __construct(string $message, public readonly string $codeName = 'payment_declined')
    {
        parent::__construct($message);
    }
}
