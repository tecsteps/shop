<?php

namespace App\Services;

use App\Enums\InventoryPolicy;
use App\Exceptions\InsufficientInventoryException;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function checkAvailability(InventoryItem $item, int $quantity): bool
    {
        $available = $item->quantity_on_hand - $item->quantity_reserved;

        if ($item->policy === InventoryPolicy::Continue) {
            return true;
        }

        return $available >= $quantity;
    }

    public function reserve(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity) {
            $item->lockForUpdate();
            $item->refresh();

            $available = $item->quantity_on_hand - $item->quantity_reserved;

            if ($item->policy === InventoryPolicy::Deny && $available < $quantity) {
                throw new InsufficientInventoryException(
                    requested: $quantity,
                    available: $available,
                );
            }

            $item->increment('quantity_reserved', $quantity);
        });
    }

    public function release(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity) {
            $item->lockForUpdate();
            $item->refresh();

            $releaseAmount = min($quantity, $item->quantity_reserved);
            if ($releaseAmount > 0) {
                $item->decrement('quantity_reserved', $releaseAmount);
            }
        });
    }

    public function commit(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity) {
            $item->lockForUpdate();
            $item->refresh();

            $item->decrement('quantity_on_hand', $quantity);
            $item->decrement('quantity_reserved', $quantity);
        });
    }

    public function restock(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity) {
            $item->lockForUpdate();
            $item->refresh();

            $item->increment('quantity_on_hand', $quantity);
        });
    }
}
