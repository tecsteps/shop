<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidDiscountException extends RuntimeException
{
    public function __construct(string $message = 'This discount code is not valid.', public readonly string $reason = 'invalid')
    {
        parent::__construct($message, 422);
    }
}
