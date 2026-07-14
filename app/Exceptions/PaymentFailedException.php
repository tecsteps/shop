<?php

namespace App\Exceptions;

class PaymentFailedException extends DomainException
{
    public function __construct(public readonly string $errorCode, ?string $message = null)
    {
        parent::__construct($message ?? 'Payment could not be completed.');
    }
}
