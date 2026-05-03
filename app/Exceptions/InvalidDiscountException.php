<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidDiscountException extends RuntimeException
{
    public function __construct(
        public readonly string $reasonCode,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function because(string $reasonCode, string $message): self
    {
        return new self($reasonCode, $message);
    }
}
