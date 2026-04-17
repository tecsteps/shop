<?php

namespace App\Services;

use App\Enums\InventoryPolicy;
use App\Exceptions\InsufficientInventoryException;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Check if the requested quantity is available for the given inventory item.
     */
    public function checkAvailability(InventoryItem $item, int $quantity): bool
    {
        if ($item->policy === InventoryPolicy::Continue) {
            return true;
        }

        return $item->quantityAvailable() >= $quantity;
    }

    /**
     * Reserve inventory for an order. Throws if policy is "deny" and insufficient stock.
     *
     * @throws InsufficientInventoryException
     */
    public function reserve(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity) {
            $item->refresh();

            if ($item->policy === InventoryPolicy::Deny) {
                if ($item->quantityAvailable() < $quantity) {
                    throw new InsufficientInventoryException(
                        $item->variant_id,
                        $quantity,
                        $item->quantityAvailable(),
                    );
                }
            }

            $item->increment('quantity_reserved', $quantity);
        });
    }

    /**
     * Release previously reserved inventory.
     */
    public function release(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity) {
            $item->refresh();

            $release = min($quantity, $item->quantity_reserved);
            $item->decrement('quantity_reserved', $release);
        });
    }

    /**
     * Commit reserved inventory after payment confirmation.
     * Decrements both on_hand and reserved.
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
     * Restock inventory (e.g., after a refund with restock flag).
     */
    public function restock(InventoryItem $item, int $quantity): void
    {
        $item->increment('quantity_on_hand', $quantity);
    }
}
