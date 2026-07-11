<?php

namespace App\Exceptions;

use RuntimeException;

class PaymentFailedException extends RuntimeException
{
    public function __construct(public readonly string $errorCode)
    {
        parent::__construct(match ($errorCode) {
            'insufficient_funds' => 'The card has insufficient funds.',
            default => 'The payment was declined.',
        });
    }
}
