<?php

namespace App\Exceptions;

use RuntimeException;

class CheckoutStateException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $status = 409,
        public readonly ?string $errorCode = null,
    ) {
        parent::__construct($message);
    }
}
