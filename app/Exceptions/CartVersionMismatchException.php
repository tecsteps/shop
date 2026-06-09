<?php

namespace App\Exceptions;

use Exception;

class CartVersionMismatchException extends Exception
{
    public static function forVersions(int $expected, int $current): self
    {
        return new self("Cart version mismatch: expected {$expected}, current {$current}.");
    }
}
