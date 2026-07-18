<?php

namespace App\Exceptions;

use Exception;

class PaymentFailedException extends Exception
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct($reason);
    }
}
