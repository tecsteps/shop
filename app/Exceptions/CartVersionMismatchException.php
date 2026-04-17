<?php

namespace App\Exceptions;

use RuntimeException;

class CartVersionMismatchException extends RuntimeException
{
    public function __construct(
        public readonly int $expected,
        public readonly int $actual,
    ) {
        parent::__construct("Cart version mismatch: expected {$expected}, got {$actual}.");
    }
}
