<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientInventoryException extends RuntimeException
{
    public function __construct(
        public int $variantId,
        public int $requested,
        public int $available,
        ?string $message = null,
    ) {
        parent::__construct($message ?? "Insufficient inventory for variant {$variantId}: requested {$requested}, available {$available}.");
    }
}
