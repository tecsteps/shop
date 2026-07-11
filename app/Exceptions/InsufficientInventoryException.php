<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientInventoryException extends RuntimeException
{
    public function __construct(
        public readonly int $inventoryItemId,
        public readonly int $requested,
        public readonly int $available,
    ) {
        parent::__construct("Requested {$requested} units but only {$available} are available.");
    }

    /** @return array<string, int> */
    public function context(): array
    {
        return [
            'inventory_item_id' => $this->inventoryItemId,
            'requested' => $this->requested,
            'available' => $this->available,
        ];
    }
}
