<?php

namespace App\Exceptions;

use Exception;

class InsufficientInventoryException extends Exception
{
    public static function forQuantity(int $requested, int $available): self
    {
        return new self("Insufficient inventory: requested {$requested}, available {$available}.");
    }
}
