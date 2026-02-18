<?php

namespace App\Services;

use App\Enums\InventoryPolicy;
use App\Exceptions\InsufficientInventoryException;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Check if sufficient available inventory exists.
     */
    public function checkAvailability(InventoryItem $item, int $quantity): bool
    {
        if ($item->policy === InventoryPolicy::Continue) {
            return true;
        }

        return $item->quantityAvailable() >= $quantity;
    }

    /**
     * Reserve inventory by incrementing quantity_reserved.
     *
     * @throws InsufficientInventoryException
     */
    public function reserve(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity) {
            $item->refresh();

            if ($item->policy === InventoryPolicy::Deny && $item->quantityAvailable() < $quantity) {
                throw new InsufficientInventoryException(
                    $item->variant_id,
                    $quantity,
                    $item->quantityAvailable()
                );
            }

            $item->increment('quantity_reserved', $quantity);
        });
    }

    /**
     * Release reserved inventory by decrementing quantity_reserved.
     */
    public function release(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity) {
            $item->refresh();
            $item->decrement('quantity_reserved', min($quantity, $item->quantity_reserved));
        });
    }

    /**
     * Commit reserved inventory by decrementing both on_hand and reserved.
     */
    public function commit(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity) {
            $item->refresh();
            $item->decrement('quantity_on_hand', $quantity);
            $item->decrement('quantity_reserved', min($quantity, $item->quantity_reserved));
        });
    }

    /**
     * Restock inventory by incrementing quantity_on_hand.
     */
    public function restock(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity) {
            $item->increment('quantity_on_hand', $quantity);
        });
    }
}
