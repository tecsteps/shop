<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientInventoryException extends RuntimeException
{
    public function __construct(
        public readonly int $requested,
        public readonly int $available,
        string $message = 'Insufficient inventory to fulfill the request.',
    ) {
        parent::__construct($message);
    }
}
