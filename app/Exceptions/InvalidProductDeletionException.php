<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidProductDeletionException extends RuntimeException
{
    public function __construct(public readonly int $productId, string $reason)
    {
        parent::__construct("Product {$productId} cannot be deleted: {$reason}");
    }

    /** @return array<string, int> */
    public function context(): array
    {
        return ['product_id' => $this->productId];
    }
}
