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

        return $item->availableQuantity() >= $quantity;
    }

    public function reserve(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity): void {
            $locked = InventoryItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();

            if (! $this->checkAvailability($locked, $quantity)) {
                throw new InsufficientInventoryException;
            }

            $locked->quantity_reserved += $quantity;
            $locked->save();
        });
    }

    public function release(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity): void {
            $locked = InventoryItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();
            $locked->quantity_reserved = max(0, $locked->quantity_reserved - $quantity);
            $locked->save();
        });
    }

    public function commit(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity): void {
            $locked = InventoryItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();
            $locked->quantity_on_hand = max(0, $locked->quantity_on_hand - $quantity);
            $locked->quantity_reserved = max(0, $locked->quantity_reserved - $quantity);
            $locked->save();
        });
    }

    public function restock(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity): void {
            $locked = InventoryItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();
            $locked->quantity_on_hand += $quantity;
            $locked->save();
        });
    }
}
