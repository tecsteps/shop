<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidVariantMatrixException extends RuntimeException
{
    public function __construct(public readonly int $productId, string $reason)
    {
        parent::__construct("The variant matrix for product {$productId} is invalid: {$reason}");
    }
}
