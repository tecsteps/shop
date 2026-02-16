<?php

namespace App\Exceptions;

use RuntimeException;

class CartVersionMismatchException extends RuntimeException
{
    public function __construct(string $message = 'Cart version mismatch')
    {
        parent::__construct($message);
    }
}
