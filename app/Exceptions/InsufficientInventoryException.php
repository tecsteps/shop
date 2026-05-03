<?php

namespace App\Exceptions;

use Exception;

class InsufficientInventoryException extends Exception
{
    public static function forQuantity(int $available, int $requested): self
    {
        return new self("Only {$available} units are available; {$requested} requested.");
    }
}
