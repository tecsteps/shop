<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when the payment provider rejects a charge (decline, insufficient
 * funds). Carries the provider error code for API responses (spec 02 §2.3).
 */
class PaymentFailedException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message = 'The payment failed.',
    ) {
        parent::__construct($message);
    }
}
