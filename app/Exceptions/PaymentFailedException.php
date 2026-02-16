<?php

namespace App\Exceptions;

use RuntimeException;

class PaymentFailedException extends RuntimeException
{
    public function __construct(string $message = 'Payment failed')
    {
        parent::__construct($message);
    }
}
