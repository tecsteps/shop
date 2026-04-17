<?php

namespace App\Services\Inventory;

use RuntimeException;

class InsufficientInventoryException extends RuntimeException
{
    public function __construct(public readonly int $variantId, public readonly int $requested, public readonly int $available)
    {
        parent::__construct(sprintf(
            'Insufficient inventory for variant %d (requested %d, available %d).',
            $variantId,
            $requested,
            $available,
        ));
    }
}
