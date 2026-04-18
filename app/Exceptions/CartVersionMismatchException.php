<?php

namespace App\Exceptions;

use Exception;

class CartVersionMismatchException extends Exception
{
    public function __construct(
        public readonly int $expected,
        public readonly int $current,
    ) {
        parent::__construct("Cart version mismatch: expected {$expected}, current {$current}.");
    }
}
