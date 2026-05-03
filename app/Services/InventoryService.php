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
        $this->guardPositiveQuantity($quantity);

        return $item->policy === InventoryPolicy::Continue || $item->availableQuantity() >= $quantity;
    }

    public function reserve(InventoryItem $item, int $quantity): void
    {
        $this->mutate($item, $quantity, function (InventoryItem $locked, int $quantity): void {
            if (! $this->checkAvailability($locked, $quantity)) {
                throw new InsufficientInventoryException('Insufficient inventory available.');
            }

            $locked->increment('quantity_reserved', $quantity);
        });
    }

    public function release(InventoryItem $item, int $quantity): void
    {
        $this->mutate($item, $quantity, function (InventoryItem $locked, int $quantity): void {
            $locked->forceFill([
                'quantity_reserved' => max(0, $locked->quantity_reserved - $quantity),
            ])->save();
        });
    }

    public function commit(InventoryItem $item, int $quantity): void
    {
        $this->mutate($item, $quantity, function (InventoryItem $locked, int $quantity): void {
            $locked->forceFill([
                'quantity_on_hand' => $locked->quantity_on_hand - $quantity,
                'quantity_reserved' => max(0, $locked->quantity_reserved - $quantity),
            ])->save();
        });
    }

    public function restock(InventoryItem $item, int $quantity): void
    {
        $this->mutate($item, $quantity, function (InventoryItem $locked, int $quantity): void {
            $locked->increment('quantity_on_hand', $quantity);
        });
    }

    /**
     * @param  callable(InventoryItem, int): void  $callback
     */
    private function mutate(InventoryItem $item, int $quantity, callable $callback): void
    {
        $this->guardPositiveQuantity($quantity);

        DB::transaction(function () use ($item, $quantity, $callback): void {
            $locked = InventoryItem::withoutGlobalScopes()
                ->whereKey($item->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $callback($locked, $quantity);
        });
    }

    private function guardPositiveQuantity(int $quantity): void
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be greater than zero.');
        }
    }
}
