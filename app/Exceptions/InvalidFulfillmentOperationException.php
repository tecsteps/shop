<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidFulfillmentOperationException extends RuntimeException
{
    public static function because(string $reason): self
    {
        return new self($reason);
    }
}
