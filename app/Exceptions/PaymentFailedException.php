<?php

namespace App\Exceptions;

use Exception;

class PaymentFailedException extends Exception
{
    public function __construct(public readonly string $errorCode)
    {
        parent::__construct(match ($errorCode) {
            'card_declined' => 'Payment declined: your card was declined.',
            'insufficient_funds' => 'Payment declined: insufficient funds.',
            default => "Payment failed ({$errorCode}).",
        });
    }
}
