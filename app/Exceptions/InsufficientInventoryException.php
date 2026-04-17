<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\InventoryItem;
use RuntimeException;

final class InsufficientInventoryException extends RuntimeException
{
    public function __construct(
        public readonly int $inventoryItemId,
        public readonly int $variantId,
        public readonly int $requestedQuantity,
        public readonly int $availableQuantity,
    ) {
        parent::__construct(sprintf(
            'Insufficient inventory for variant %d. Requested %d, available %d.',
            $variantId,
            $requestedQuantity,
            $availableQuantity,
        ));
    }

    public static function forItem(InventoryItem $item, int $requestedQuantity): self
    {
        return new self(
            inventoryItemId: (int) $item->id,
            variantId: (int) $item->variant_id,
            requestedQuantity: $requestedQuantity,
            availableQuantity: (int) $item->quantity_on_hand - (int) $item->quantity_reserved,
        );
    }
}
