<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidCheckoutTransitionException extends RuntimeException
{
    public function __construct(string $message = 'Invalid checkout state transition')
    {
        parent::__construct($message);
    }
}
