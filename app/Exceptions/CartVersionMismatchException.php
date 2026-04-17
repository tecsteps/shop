<?php

namespace App\Exceptions;

use RuntimeException;

class CartVersionMismatchException extends RuntimeException
{
    public function __construct(
        public readonly int $expectedVersion,
        public readonly int $currentVersion,
    ) {
        parent::__construct(
            "Cart version mismatch: expected {$expectedVersion}, current {$currentVersion}"
        );
    }
}
