<?php

namespace App\Services;

use App\Exceptions\InsufficientInventoryException;
use App\Models\InventoryItem;
use BackedEnum;
use Illuminate\Support\Facades\DB;

final class InventoryService
{
    public function available(InventoryItem $item): int
    {
        return $item->quantity_on_hand - $item->quantity_reserved;
    }

    public function checkAvailability(InventoryItem $item, int $quantity): bool
    {
        return $this->policy($item) === 'continue' || $this->available($item) >= $quantity;
    }

    public function reserve(InventoryItem $item, int $quantity): void
    {
        $this->mutate($item, function (InventoryItem $locked) use ($quantity): void {
            $this->positive($quantity);

            if (! $this->checkAvailability($locked, $quantity)) {
                throw new InsufficientInventoryException('Not enough inventory is available.');
            }

            $locked->increment('quantity_reserved', $quantity);
        });
    }

    public function release(InventoryItem $item, int $quantity): void
    {
        $this->mutate($item, function (InventoryItem $locked) use ($quantity): void {
            $this->positive($quantity);
            $locked->quantity_reserved = max(0, $locked->quantity_reserved - $quantity);
            $locked->save();
        });
    }

    public function commit(InventoryItem $item, int $quantity): void
    {
        $this->mutate($item, function (InventoryItem $locked) use ($quantity): void {
            $this->positive($quantity);
            $locked->quantity_on_hand -= $quantity;
            $locked->quantity_reserved = max(0, $locked->quantity_reserved - $quantity);
            $locked->save();
        });
    }

    public function restock(InventoryItem $item, int $quantity): void
    {
        $this->mutate($item, function (InventoryItem $locked) use ($quantity): void {
            $this->positive($quantity);
            $locked->increment('quantity_on_hand', $quantity);
        });
    }

    /** @param callable(InventoryItem): void $callback */
    private function mutate(InventoryItem $item, callable $callback): void
    {
        DB::transaction(function () use ($item, $callback): void {
            /** @var InventoryItem $locked */
            $locked = InventoryItem::withoutGlobalScopes()->lockForUpdate()->findOrFail($item->getKey());
            $callback($locked);
            $item->refresh();
        });
    }

    private function policy(InventoryItem $item): string
    {
        return $item->policy instanceof BackedEnum ? (string) $item->policy->value : (string) $item->policy;
    }

    private function positive(int $quantity): void
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('Quantity must be at least one.');
        }
    }
}
