<?php

namespace App\Services;

use App\Enums\InventoryPolicy;
use App\Exceptions\InsufficientInventoryException;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    public function checkAvailability(InventoryItem $item, int $quantity): bool
    {
        if ($quantity < 0) {
            return false;
        }

        return $item->policy === InventoryPolicy::Continue->value
            || ($item->quantity_on_hand - $item->quantity_reserved) >= $quantity;
    }

    public function reserve(InventoryItem $item, int $quantity): void
    {
        $this->assertPositive($quantity);

        DB::transaction(function () use ($item, $quantity): void {
            $locked = InventoryItem::query()->whereKey($item->getKey())->lockForUpdate()->firstOrFail();

            if (! $this->checkAvailability($locked, $quantity)) {
                throw new InsufficientInventoryException('There is not enough inventory to reserve this quantity.');
            }

            $locked->increment('quantity_reserved', $quantity);
            $item->refresh();
        });
    }

    public function release(InventoryItem $item, int $quantity): void
    {
        $this->assertPositive($quantity);

        DB::transaction(function () use ($item, $quantity): void {
            $locked = InventoryItem::query()->whereKey($item->getKey())->lockForUpdate()->firstOrFail();
            $locked->quantity_reserved = max(0, $locked->quantity_reserved - $quantity);
            $locked->save();
            $item->refresh();
        });
    }

    public function commit(InventoryItem $item, int $quantity): void
    {
        $this->assertPositive($quantity);

        DB::transaction(function () use ($item, $quantity): void {
            $locked = InventoryItem::query()->whereKey($item->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->quantity_reserved < $quantity) {
                throw new InsufficientInventoryException('The requested quantity has not been reserved.');
            }

            $locked->quantity_on_hand -= $quantity;
            $locked->quantity_reserved -= $quantity;
            $locked->save();
            $item->refresh();
        });
    }

    public function restock(InventoryItem $item, int $quantity): void
    {
        $this->assertPositive($quantity);

        DB::transaction(function () use ($item, $quantity): void {
            $locked = InventoryItem::query()->whereKey($item->getKey())->lockForUpdate()->firstOrFail();
            $locked->increment('quantity_on_hand', $quantity);
            $item->refresh();
        });
    }

    private function assertPositive(int $quantity): void
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Inventory quantities must be positive integers.');
        }
    }
}
