<?php

namespace App\Exceptions;

use DomainException;

class PaymentFailedException extends DomainException
{
    public function __construct(
        public string $errorCode,
        string $message,
    ) {
        parent::__construct($message);
    }
}
