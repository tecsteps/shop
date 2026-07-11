<?php

namespace App\Services;

use App\Enums\InventoryPolicy;
use App\Exceptions\InsufficientInventoryException;
use App\Exceptions\InvalidInventoryOperationException;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function checkAvailability(InventoryItem $item, int $quantity): bool
    {
        if ($quantity < 1) {
            return false;
        }

        return $item->policy === InventoryPolicy::Continue || $item->available >= $quantity;
    }

    public function reserve(InventoryItem $item, int $quantity): void
    {
        $this->assertPositiveQuantity($quantity);

        DB::transaction(function () use ($item, $quantity): void {
            $inventory = $this->lock($item);

            if (! $this->checkAvailability($inventory, $quantity)) {
                throw new InsufficientInventoryException($inventory->getKey(), $quantity, $inventory->available);
            }

            $inventory->increment('quantity_reserved', $quantity);
            $this->syncModel($item, $inventory->fresh());
        });
    }

    public function release(InventoryItem $item, int $quantity): void
    {
        $this->assertPositiveQuantity($quantity);

        DB::transaction(function () use ($item, $quantity): void {
            $inventory = $this->lock($item);

            if ($inventory->quantity_reserved < $quantity) {
                throw new InvalidInventoryOperationException('Cannot release more inventory than is reserved.');
            }

            $inventory->decrement('quantity_reserved', $quantity);
            $this->syncModel($item, $inventory->fresh());
        });
    }

    public function commit(InventoryItem $item, int $quantity): void
    {
        $this->assertPositiveQuantity($quantity);

        DB::transaction(function () use ($item, $quantity): void {
            $inventory = $this->lock($item);

            if ($inventory->quantity_reserved < $quantity) {
                throw new InvalidInventoryOperationException('Cannot commit more inventory than is reserved.');
            }

            $inventory->update([
                'quantity_on_hand' => $inventory->quantity_on_hand - $quantity,
                'quantity_reserved' => $inventory->quantity_reserved - $quantity,
            ]);

            $this->syncModel($item, $inventory);
        });
    }

    public function restock(InventoryItem $item, int $quantity): void
    {
        $this->assertPositiveQuantity($quantity);

        DB::transaction(function () use ($item, $quantity): void {
            $inventory = $this->lock($item);
            $inventory->increment('quantity_on_hand', $quantity);
            $this->syncModel($item, $inventory->fresh());
        });
    }

    private function assertPositiveQuantity(int $quantity): void
    {
        if ($quantity < 1) {
            throw new InvalidInventoryOperationException('Inventory quantities must be greater than zero.');
        }
    }

    private function lock(InventoryItem $item): InventoryItem
    {
        return InventoryItem::withoutGlobalScopes()->whereKey($item->getKey())->lockForUpdate()->firstOrFail();
    }

    private function syncModel(InventoryItem $target, ?InventoryItem $source): void
    {
        if ($source !== null) {
            $target->setRawAttributes($source->getAttributes(), true);
        }
    }
}
