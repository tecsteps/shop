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
        $this->assertPositiveQuantity($quantity);

        if ($item->policy === InventoryPolicy::Continue) {
            return true;
        }

        return $this->available($item) >= $quantity;
    }

    public function reserve(InventoryItem $item, int $quantity): void
    {
        $this->assertPositiveQuantity($quantity);

        DB::transaction(function () use ($item, $quantity): void {
            $locked = $this->freshItem($item);

            if (! $this->checkAvailability($locked, $quantity)) {
                throw InsufficientInventoryException::forQuantity($this->available($locked), $quantity);
            }

            $locked->forceFill([
                'quantity_reserved' => $locked->quantity_reserved + $quantity,
            ])->save();
        });
    }

    public function release(InventoryItem $item, int $quantity): void
    {
        $this->assertPositiveQuantity($quantity);

        DB::transaction(function () use ($item, $quantity): void {
            $locked = $this->freshItem($item);

            if ($locked->quantity_reserved < $quantity) {
                throw new InvalidArgumentException('Cannot release more inventory than is reserved.');
            }

            $locked->forceFill([
                'quantity_reserved' => $locked->quantity_reserved - $quantity,
            ])->save();
        });
    }

    public function commit(InventoryItem $item, int $quantity): void
    {
        $this->assertPositiveQuantity($quantity);

        DB::transaction(function () use ($item, $quantity): void {
            $locked = $this->freshItem($item);

            if ($locked->quantity_reserved < $quantity) {
                throw new InvalidArgumentException('Cannot commit more inventory than is reserved.');
            }

            $locked->forceFill([
                'quantity_on_hand' => $locked->quantity_on_hand - $quantity,
                'quantity_reserved' => $locked->quantity_reserved - $quantity,
            ])->save();
        });
    }

    public function restock(InventoryItem $item, int $quantity): void
    {
        $this->assertPositiveQuantity($quantity);

        DB::transaction(function () use ($item, $quantity): void {
            $locked = $this->freshItem($item);

            $locked->forceFill([
                'quantity_on_hand' => $locked->quantity_on_hand + $quantity,
            ])->save();
        });
    }

    private function available(InventoryItem $item): int
    {
        return $item->quantity_on_hand - $item->quantity_reserved;
    }

    private function freshItem(InventoryItem $item): InventoryItem
    {
        return InventoryItem::withoutGlobalScopes()
            ->whereKey($item->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function assertPositiveQuantity(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Inventory quantity must be greater than zero.');
        }
    }
}
