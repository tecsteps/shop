<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InventoryPolicy;
use App\Exceptions\InsufficientInventoryException;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class InventoryService
{
    public function available(InventoryItem $item): int
    {
        return $this->availableQuantity($item);
    }

    public function availableQuantity(InventoryItem $item): int
    {
        return (int) $item->quantity_on_hand - (int) $item->quantity_reserved;
    }

    public function checkAvailability(InventoryItem $item, int $quantity): bool
    {
        if ($quantity < 0) {
            throw new InvalidArgumentException('Quantity must be zero or greater.');
        }

        if ($item->policy === InventoryPolicy::Continue) {
            return true;
        }

        return $this->availableQuantity($item) >= $quantity;
    }

    public function reserve(InventoryItem $item, int $quantity): InventoryItem
    {
        $this->assertPositiveQuantity($quantity);

        /** @var InventoryItem $updated */
        $updated = DB::transaction(function () use ($item, $quantity): InventoryItem {
            $locked = $this->lockItem($item);

            if (! $this->checkAvailability($locked, $quantity)) {
                throw InsufficientInventoryException::forItem($locked, $quantity);
            }

            $locked->quantity_reserved = (int) $locked->quantity_reserved + $quantity;
            $locked->save();

            return $locked;
        });

        $this->syncItem($item, $updated);

        return $updated;
    }

    public function release(InventoryItem $item, int $quantity): InventoryItem
    {
        $this->assertPositiveQuantity($quantity);

        /** @var InventoryItem $updated */
        $updated = DB::transaction(function () use ($item, $quantity): InventoryItem {
            $locked = $this->lockItem($item);

            $locked->quantity_reserved = max(0, (int) $locked->quantity_reserved - $quantity);
            $locked->save();

            return $locked;
        });

        $this->syncItem($item, $updated);

        return $updated;
    }

    public function commit(InventoryItem $item, int $quantity): InventoryItem
    {
        $this->assertPositiveQuantity($quantity);

        /** @var InventoryItem $updated */
        $updated = DB::transaction(function () use ($item, $quantity): InventoryItem {
            $locked = $this->lockItem($item);

            $locked->quantity_on_hand = (int) $locked->quantity_on_hand - $quantity;
            $locked->quantity_reserved = max(0, (int) $locked->quantity_reserved - $quantity);
            $locked->save();

            return $locked;
        });

        $this->syncItem($item, $updated);

        return $updated;
    }

    public function restock(InventoryItem $item, int $quantity): InventoryItem
    {
        $this->assertPositiveQuantity($quantity);

        /** @var InventoryItem $updated */
        $updated = DB::transaction(function () use ($item, $quantity): InventoryItem {
            $locked = $this->lockItem($item);

            $locked->quantity_on_hand = (int) $locked->quantity_on_hand + $quantity;
            $locked->save();

            return $locked;
        });

        $this->syncItem($item, $updated);

        return $updated;
    }

    private function assertPositiveQuantity(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be greater than zero.');
        }
    }

    private function lockItem(InventoryItem $item): InventoryItem
    {
        /** @var InventoryItem $locked */
        $locked = InventoryItem::query()
            ->whereKey($item->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        return $locked;
    }

    private function syncItem(InventoryItem $target, InventoryItem $source): void
    {
        $target->quantity_on_hand = (int) $source->quantity_on_hand;
        $target->quantity_reserved = (int) $source->quantity_reserved;
        $target->policy = $source->policy;
    }
}
