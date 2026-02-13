<?php

namespace App\Exceptions;

use App\Enums\ProductStatus;
use RuntimeException;

class InvalidProductTransitionException extends RuntimeException
{
    public function __construct(
        public readonly ProductStatus $from,
        public readonly ProductStatus $to,
        string $message = '',
    ) {
        parent::__construct($message ?: "Cannot transition product from {$from->value} to {$to->value}.");
    }
}
