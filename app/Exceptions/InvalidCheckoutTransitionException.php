<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidCheckoutTransitionException extends RuntimeException
{
    public function __construct(
        public readonly string $from,
        public readonly string $to,
        string $message = 'Invalid checkout state transition',
    ) {
        parent::__construct($message);
    }
}
