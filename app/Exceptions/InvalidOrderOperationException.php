<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidOrderOperationException extends RuntimeException
{
    public static function because(string $reason): self
    {
        return new self($reason);
    }
}
