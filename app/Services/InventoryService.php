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
        if ($item->policy === InventoryPolicy::Continue) {
            return true;
        }

        return $item->quantity_available >= $quantity;
    }

    public function reserve(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity) {
            $item->lockForUpdate();
            $item->refresh();

            if (! $this->checkAvailability($item, $quantity)) {
                throw new InsufficientInventoryException(
                    requested: $quantity,
                    available: $item->quantity_available,
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

            $item->decrement('quantity_reserved', min($quantity, $item->quantity_reserved));
        });
    }

    public function commit(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity) {
            $item->lockForUpdate();
            $item->refresh();

            $item->decrement('quantity_on_hand', $quantity);
            $item->decrement('quantity_reserved', min($quantity, $item->quantity_reserved));
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
