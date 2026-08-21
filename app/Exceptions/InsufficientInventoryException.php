<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientInventoryException extends RuntimeException
{
    public function __construct(string $message = 'The requested quantity is not available.')
    {
        parent::__construct($message, 422);
    }
}
