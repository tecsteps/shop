<?php

namespace App\Exceptions;

use Exception;

class InsufficientInventoryException extends Exception
{
    public function __construct(string $message = 'Insufficient inventory available.')
    {
        parent::__construct($message);
    }
}
