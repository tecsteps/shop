<?php

namespace App\Services;

use App\Enums\InventoryPolicy;
use App\Exceptions\InsufficientInventoryException;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;

/**
 * Stock lifecycle operations per spec 05 section 3.3.
 * All mutations run inside transactions with row locks.
 */
class InventoryService
{
    public function available(InventoryItem $item): int
    {
        return $item->quantity_on_hand - $item->quantity_reserved;
    }

    public function checkAvailability(InventoryItem $item, int $quantity): bool
    {
        if ($item->policy === InventoryPolicy::Continue) {
            return true;
        }

        return $this->available($item) >= $quantity;
    }

    public function isLowStock(InventoryItem $item, int $threshold = 5): bool
    {
        return $this->available($item) <= $threshold;
    }

    public function reserve(InventoryItem $item, int $quantity): InventoryItem
    {
        return DB::transaction(function () use ($item, $quantity): InventoryItem {
            $locked = $this->locked($item);

            if (! $this->checkAvailability($locked, $quantity)) {
                throw new InsufficientInventoryException(
                    "Insufficient inventory: requested {$quantity}, available {$this->available($locked)}."
                );
            }

            $locked->increment('quantity_reserved', $quantity);

            return $locked->refresh();
        });
    }

    public function release(InventoryItem $item, int $quantity): InventoryItem
    {
        return DB::transaction(function () use ($item, $quantity): InventoryItem {
            $locked = $this->locked($item);
            $locked->update(['quantity_reserved' => max(0, $locked->quantity_reserved - $quantity)]);

            return $locked->refresh();
        });
    }

    public function commit(InventoryItem $item, int $quantity): InventoryItem
    {
        return DB::transaction(function () use ($item, $quantity): InventoryItem {
            $locked = $this->locked($item);
            $locked->update([
                'quantity_on_hand' => $locked->quantity_on_hand - $quantity,
                'quantity_reserved' => max(0, $locked->quantity_reserved - $quantity),
            ]);

            return $locked->refresh();
        });
    }

    public function restock(InventoryItem $item, int $quantity): InventoryItem
    {
        return DB::transaction(function () use ($item, $quantity): InventoryItem {
            $locked = $this->locked($item);
            $locked->increment('quantity_on_hand', $quantity);

            return $locked->refresh();
        });
    }

    public function adjust(InventoryItem $item, ?int $quantityOnHand = null, ?InventoryPolicy $policy = null): InventoryItem
    {
        return DB::transaction(function () use ($item, $quantityOnHand, $policy): InventoryItem {
            $locked = $this->locked($item);

            if ($quantityOnHand !== null) {
                $locked->quantity_on_hand = $quantityOnHand;
            }

            if ($policy !== null) {
                $locked->policy = $policy;
            }

            $locked->save();

            return $locked->refresh();
        });
    }

    protected function locked(InventoryItem $item): InventoryItem
    {
        return InventoryItem::query()->whereKey($item->getKey())->lockForUpdate()->firstOrFail();
    }
}
