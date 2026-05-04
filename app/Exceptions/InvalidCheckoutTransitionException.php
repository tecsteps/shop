<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidCheckoutTransitionException extends RuntimeException
{
    public static function because(string $reason): self
    {
        return new self($reason);
    }
}
