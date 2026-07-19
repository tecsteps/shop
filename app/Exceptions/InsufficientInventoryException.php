<?php

namespace App\Exceptions;

use App\Models\InventoryItem;
use RuntimeException;

class InsufficientInventoryException extends RuntimeException
{
    /**
     * Create an exception for a reservation exceeding available stock.
     */
    public static function forReservation(InventoryItem $item, int $quantity): self
    {
        return new self(
            "Insufficient inventory for variant {$item->variant_id}: requested {$quantity}, available {$item->available()}."
        );
    }
}
