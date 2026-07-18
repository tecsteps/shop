<?php

namespace App\Exceptions;

use Exception;

class InvalidProductTransitionException extends Exception
{
    public function __construct(string $message = 'Invalid product status transition.')
    {
        parent::__construct($message);
    }
}
