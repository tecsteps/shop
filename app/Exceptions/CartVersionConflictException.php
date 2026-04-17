<?php

namespace App\Exceptions;

use RuntimeException;

class CartVersionConflictException extends RuntimeException
{
    public function __construct(public readonly int $expected, public readonly int $actual)
    {
        parent::__construct("Cart version conflict: expected {$expected}, actual {$actual}.");
    }
}
