<?php

namespace App\Services;

use App\Enums\InventoryPolicy;
use App\Exceptions\InsufficientInventoryException;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Whether enough available stock (on hand minus reserved) exists.
     */
    public function checkAvailability(InventoryItem $item, int $quantity): bool
    {
        return $item->availableQuantity() >= $quantity;
    }

    /**
     * Reserve stock for an active checkout or pending order.
     *
     * @throws InsufficientInventoryException
     */
    public function reserve(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity): void {
            $item->refresh();

            if ($item->policy === InventoryPolicy::Deny && ! $this->checkAvailability($item, $quantity)) {
                throw InsufficientInventoryException::forQuantity($quantity, $item->availableQuantity());
            }

            $item->increment('quantity_reserved', $quantity);
        });
    }

    /**
     * Release a reservation when a checkout expires or is abandoned.
     */
    public function release(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity): void {
            $item->decrement('quantity_reserved', $quantity);
        });
    }

    /**
     * Commit reserved stock when payment is confirmed and the order created.
     */
    public function commit(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity): void {
            $item->decrement('quantity_on_hand', $quantity);
            $item->decrement('quantity_reserved', $quantity);
        });
    }

    /**
     * Return stock when a refund is processed with the restock flag.
     */
    public function restock(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity): void {
            $item->increment('quantity_on_hand', $quantity);
        });
    }
}
