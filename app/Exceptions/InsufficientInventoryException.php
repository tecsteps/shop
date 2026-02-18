<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientInventoryException extends RuntimeException
{
    public function __construct(
        public readonly int $variantId,
        public readonly int $requested,
        public readonly int $available,
    ) {
        parent::__construct(
            "Insufficient inventory for variant {$variantId}: requested {$requested}, available {$available}."
        );
    }
}
