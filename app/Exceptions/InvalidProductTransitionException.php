<?php

namespace App\Exceptions;

use App\Enums\ProductStatus;
use Exception;

class InvalidProductTransitionException extends Exception
{
    public static function between(ProductStatus $from, ProductStatus $to, string $reason): self
    {
        return new self("Cannot transition product from {$from->value} to {$to->value}: {$reason}");
    }
}
