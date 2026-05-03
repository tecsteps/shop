<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidCartOperationException extends RuntimeException
{
    public static function because(string $reason): self
    {
        return new self($reason);
    }
}
