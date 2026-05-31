<?php

namespace App\Services;

use App\Enums\InventoryPolicy;
use App\Exceptions\InsufficientInventoryException;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;

/**
 * Manages stock levels across the reserve -> commit / release lifecycle.
 *
 * Available stock is `quantity_on_hand - quantity_reserved`. Reservations hold
 * stock for in-progress checkouts; committing deducts physical stock when an
 * order is paid; releasing frees a reservation when a checkout expires; and
 * restocking returns physical stock (for example on a refund with restock).
 *
 * Every mutation runs inside a database transaction. SQLite's single-writer WAL
 * mode plus transactional isolation is sufficient here (no advisory locks).
 */
class InventoryService
{
    /**
     * Whether at least `$quantity` units are available for sale.
     */
    public function checkAvailability(InventoryItem $item, int $quantity): bool
    {
        return $item->available() >= $quantity;
    }

    /**
     * Hold `$quantity` units for an in-progress checkout.
     *
     * Under a `deny` policy this verifies sufficient availability first and
     * throws {@see InsufficientInventoryException} otherwise. A `continue`
     * policy allows overselling (reserved may exceed on hand).
     *
     * @throws InsufficientInventoryException
     */
    public function reserve(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity): void {
            $item->refresh();

            if ($item->policy === InventoryPolicy::Deny && ! $this->checkAvailability($item, $quantity)) {
                throw new InsufficientInventoryException(
                    "Insufficient inventory for variant {$item->variant_id}: requested {$quantity}, available {$item->available()}.",
                );
            }

            $item->quantity_reserved += $quantity;
            $item->save();
        });
    }

    /**
     * Free a reservation (for example when a checkout expires or is abandoned).
     */
    public function release(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity): void {
            $item->refresh();

            $item->quantity_reserved = max(0, $item->quantity_reserved - $quantity);
            $item->save();
        });
    }

    /**
     * Deduct physical stock once an order is paid: decrement both on hand and
     * reserved (the reservation was already validated).
     */
    public function commit(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity): void {
            $item->refresh();

            $item->quantity_on_hand -= $quantity;
            $item->quantity_reserved = max(0, $item->quantity_reserved - $quantity);
            $item->save();
        });
    }

    /**
     * Return physical stock (for example a refund processed with the restock
     * flag set).
     */
    public function restock(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity): void {
            $item->refresh();

            $item->quantity_on_hand += $quantity;
            $item->save();
        });
    }
}
