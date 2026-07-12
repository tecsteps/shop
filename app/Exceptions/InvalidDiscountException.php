<?php

namespace App\Exceptions;

class InvalidDiscountException extends DomainException
{
    public function __construct(public readonly string $reason, ?string $message = null)
    {
        parent::__construct($message ?? str_replace('_', ' ', $reason));
    }
}
