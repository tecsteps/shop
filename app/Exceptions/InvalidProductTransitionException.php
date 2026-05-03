<?php

namespace App\Exceptions;

use Exception;

class InvalidProductTransitionException extends Exception
{
    public static function because(string $reason): self
    {
        return new self($reason);
    }
}
