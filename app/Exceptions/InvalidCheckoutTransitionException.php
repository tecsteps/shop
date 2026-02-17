<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidCheckoutTransitionException extends RuntimeException
{
    public function __construct(
        public readonly string $from,
        public readonly string $to,
    ) {
        parent::__construct("Invalid checkout transition from '{$from}' to '{$to}'");
    }
}
