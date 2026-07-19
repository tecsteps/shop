<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a checkout state transition is not allowed from the current
 * status (spec 05 §6 state machine). Mapped to HTTP 422 by the API.
 */
class InvalidCheckoutTransitionException extends RuntimeException
{
    public static function make(string $from, string $transition): self
    {
        return new self("Cannot perform [{$transition}] from checkout status [{$from}].");
    }
}
