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

        return $item->available() >= $quantity;
    }

    public function reserve(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity): void {
            $item = InventoryItem::withoutGlobalScopes()->lockForUpdate()->find($item->id);

            if ($item->policy === InventoryPolicy::Deny && $item->available() < $quantity) {
                throw new InsufficientInventoryException(
                    requested: $quantity,
                    available: $item->available(),
                );
            }

            $item->update([
                'quantity_reserved' => $item->quantity_reserved + $quantity,
            ]);
        });
    }

    public function release(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity): void {
            $item = InventoryItem::withoutGlobalScopes()->lockForUpdate()->find($item->id);

            $item->update([
                'quantity_reserved' => max(0, $item->quantity_reserved - $quantity),
            ]);
        });
    }

    public function commit(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity): void {
            $item = InventoryItem::withoutGlobalScopes()->lockForUpdate()->find($item->id);

            $item->update([
                'quantity_on_hand' => $item->quantity_on_hand - $quantity,
                'quantity_reserved' => max(0, $item->quantity_reserved - $quantity),
            ]);
        });
    }

    public function restock(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity): void {
            $item = InventoryItem::withoutGlobalScopes()->lockForUpdate()->find($item->id);

            $item->update([
                'quantity_on_hand' => $item->quantity_on_hand + $quantity,
            ]);
        });
    }
}
