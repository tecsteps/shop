<?php

namespace App\Exceptions;

use RuntimeException;

class PaymentFailedException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        ?string $message = null,
    ) {
        parent::__construct($message ?? "Payment failed: {$errorCode}.");
    }
}
