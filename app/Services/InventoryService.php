<?php

namespace App\Services;

use App\Enums\InventoryPolicy;
use App\Exceptions\InsufficientInventoryException;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Check if sufficient available inventory exists for the given quantity.
     */
    public function checkAvailability(InventoryItem $item, int $quantity): bool
    {
        if ($item->policy === InventoryPolicy::Continue) {
            return true;
        }

        return $item->availableQuantity() >= $quantity;
    }

    /**
     * Reserve inventory for a checkout or pending order.
     * Increments quantity_reserved without changing quantity_on_hand.
     *
     * @throws InsufficientInventoryException
     */
    public function reserve(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity) {
            $item->refresh();

            if ($item->policy === InventoryPolicy::Deny && $item->availableQuantity() < $quantity) {
                throw new InsufficientInventoryException(
                    $item->variant_id,
                    $quantity,
                    $item->availableQuantity(),
                );
            }

            $item->increment('quantity_reserved', $quantity);
        });
    }

    /**
     * Release previously reserved inventory (e.g., checkout expired or abandoned).
     * Decrements quantity_reserved without changing quantity_on_hand.
     */
    public function release(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity) {
            $item->refresh();
            $item->decrement('quantity_reserved', $quantity);
        });
    }

    /**
     * Commit inventory when payment is confirmed.
     * Decrements both quantity_on_hand and quantity_reserved.
     */
    public function commit(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity) {
            $item->refresh();
            $item->decrement('quantity_on_hand', $quantity);
            $item->decrement('quantity_reserved', $quantity);
        });
    }

    /**
     * Restock inventory (e.g., after a refund with restock flag).
     * Increments quantity_on_hand.
     */
    public function restock(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity) {
            $item->refresh();
            $item->increment('quantity_on_hand', $quantity);
        });
    }
}
