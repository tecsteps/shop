<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidDiscountException extends RuntimeException
{
    public function __construct(
        public readonly string $reason,
        string $message = 'Invalid discount',
    ) {
        parent::__construct($message);
    }
}
