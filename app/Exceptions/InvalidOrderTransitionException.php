<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an order state transition is not allowed from the current
 * status (e.g. confirming a bank transfer that is not pending).
 */
class InvalidOrderTransitionException extends RuntimeException
{
    public static function make(string $from, string $transition): self
    {
        return new self("Cannot perform [{$transition}] from order status [{$from}].");
    }
}
