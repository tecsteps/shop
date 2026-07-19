<?php

namespace App\Services;

use App\Enums\InventoryPolicy;
use App\Exceptions\InsufficientInventoryException;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Whether the requested quantity can be reserved. With the "continue"
     * policy anything is sellable; with "deny" available stock must cover it.
     */
    public function checkAvailability(InventoryItem $item, int $quantity): bool
    {
        if ($item->policy === InventoryPolicy::Continue) {
            return true;
        }

        return $item->available() >= $quantity;
    }

    /**
     * Reserve stock for an open checkout: quantity_reserved += quantity.
     *
     * @throws InsufficientInventoryException when policy is "deny" and
     *                                        available stock is insufficient
     */
    public function reserve(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity): void {
            $item = $this->lockAndRefresh($item);

            if ($item->policy === InventoryPolicy::Deny && $item->available() < $quantity) {
                throw InsufficientInventoryException::forReservation($item, $quantity);
            }

            $item->increment('quantity_reserved', $quantity);
        });
    }

    /**
     * Release a reservation (checkout expired/abandoned): reserved -= quantity.
     */
    public function release(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity): void {
            $this->lockAndRefresh($item)->decrement('quantity_reserved', $quantity);
        });
    }

    /**
     * Commit a reservation after payment: both on_hand and reserved decrease.
     */
    public function commit(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity): void {
            $locked = $this->lockAndRefresh($item);
            $locked->decrement('quantity_on_hand', $quantity);
            $locked->decrement('quantity_reserved', $quantity);
        });
    }

    /**
     * Restock returned units after a refund: on_hand += quantity.
     */
    public function restock(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity): void {
            $this->lockAndRefresh($item)->increment('quantity_on_hand', $quantity);
        });
    }

    /**
     * Reload the item with a write lock inside the current transaction.
     */
    private function lockAndRefresh(InventoryItem $item): InventoryItem
    {
        return InventoryItem::query()->lockForUpdate()->findOrFail($item->id);
    }
}
