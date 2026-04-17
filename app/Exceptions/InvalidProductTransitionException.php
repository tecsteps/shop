<?php

namespace App\Exceptions;

use App\Enums\ProductStatus;
use RuntimeException;

class InvalidProductTransitionException extends RuntimeException
{
    public function __construct(
        public ProductStatus $from,
        public ProductStatus $to,
        ?string $message = null,
    ) {
        parent::__construct($message ?? "Invalid product status transition: {$from->value} -> {$to->value}.");
    }
}
