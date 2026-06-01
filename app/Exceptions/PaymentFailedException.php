<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when the mock payment provider declines a charge.
 *
 * The `errorCode` property carries the provider's machine code (for example
 * `card_declined` or `insufficient_funds`).
 */
class PaymentFailedException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message = '',
    ) {
        parent::__construct($message !== '' ? $message : $errorCode);
    }
}
