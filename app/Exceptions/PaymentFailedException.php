<?php

namespace App\Exceptions;

use RuntimeException;

class PaymentFailedException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        public readonly string $errorDetail,
    ) {
        parent::__construct("Payment failed: {$errorCode} - {$errorDetail}");
    }
}
