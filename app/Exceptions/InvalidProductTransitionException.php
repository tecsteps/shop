<?php

namespace App\Exceptions;

use App\Enums\ProductStatus;
use RuntimeException;

class InvalidProductTransitionException extends RuntimeException
{
    public function __construct(
        public readonly int $productId,
        public readonly ProductStatus $from,
        public readonly ProductStatus $to,
        string $reason,
    ) {
        parent::__construct("Product {$productId} cannot transition from {$from->value} to {$to->value}: {$reason}");
    }

    /** @return array<string, int|string> */
    public function context(): array
    {
        return [
            'product_id' => $this->productId,
            'from' => $this->from->value,
            'to' => $this->to->value,
        ];
    }
}
