<?php

namespace App\Services;

use App\Exceptions\InsufficientInventoryException;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function checkAvailability(InventoryItem $item, int $quantity): bool
    {
        return $item->canSell($quantity);
    }

    public function reserve(InventoryItem $item, int $quantity): void
    {
        $this->assertPositiveQuantity($quantity);

        DB::transaction(function () use ($item, $quantity): void {
            $lockedItem = InventoryItem::withoutGlobalScopes()->lockForUpdate()->findOrFail($item->getKey());

            if (! $lockedItem->canSell($quantity)) {
                throw new InsufficientInventoryException;
            }

            $lockedItem->increment('quantity_reserved', $quantity);
        });
    }

    public function release(InventoryItem $item, int $quantity): void
    {
        $this->assertPositiveQuantity($quantity);

        DB::transaction(function () use ($item, $quantity): void {
            $lockedItem = InventoryItem::withoutGlobalScopes()->lockForUpdate()->findOrFail($item->getKey());
            $lockedItem->update(['quantity_reserved' => max(0, $lockedItem->quantity_reserved - $quantity)]);
        });
    }

    public function commit(InventoryItem $item, int $quantity): void
    {
        $this->assertPositiveQuantity($quantity);

        DB::transaction(function () use ($item, $quantity): void {
            $lockedItem = InventoryItem::withoutGlobalScopes()->lockForUpdate()->findOrFail($item->getKey());
            $lockedItem->update([
                'quantity_on_hand' => $lockedItem->quantity_on_hand - $quantity,
                'quantity_reserved' => max(0, $lockedItem->quantity_reserved - $quantity),
            ]);
        });
    }

    public function restock(InventoryItem $item, int $quantity): void
    {
        $this->assertPositiveQuantity($quantity);

        DB::transaction(function () use ($item, $quantity): void {
            $lockedItem = InventoryItem::withoutGlobalScopes()->lockForUpdate()->findOrFail($item->getKey());
            $lockedItem->increment('quantity_on_hand', $quantity);
        });
    }

    private function assertPositiveQuantity(int $quantity): void
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('Inventory quantity must be positive.');
        }
    }
}
