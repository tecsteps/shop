<?php

namespace App\Exceptions;

use Exception;

class CartVersionMismatchException extends Exception
{
    public function __construct(
        public readonly int $expectedVersion,
        public readonly int $currentVersion,
    ) {
        parent::__construct("Cart version mismatch: expected {$expectedVersion}, current {$currentVersion}.");
    }

    public static function forVersions(int $expected, int $current): self
    {
        return new self($expected, $current);
    }
}
