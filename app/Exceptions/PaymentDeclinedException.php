<?php

namespace App\Exceptions;

use RuntimeException;

class PaymentDeclinedException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message = 'Payment was declined',
    ) {
        parent::__construct($message);
    }
}
