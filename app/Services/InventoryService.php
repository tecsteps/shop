<?php

namespace App\Services;

use App\Exceptions\InsufficientInventoryException;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function checkAvailability(InventoryItem $item, int $quantity): bool
    {
        return $this->available($item) >= $quantity;
    }

    public function available(InventoryItem $item): int
    {
        return $item->quantity_on_hand - $item->quantity_reserved;
    }

    public function reserve(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity) {
            $item->refresh();

            if ($item->policy === 'deny' && $this->available($item) < $quantity) {
                throw new InsufficientInventoryException('Insufficient inventory available.');
            }

            $item->quantity_reserved += $quantity;
            $item->save();
        });
    }

    public function release(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity) {
            $item->refresh();
            $item->quantity_reserved = max(0, $item->quantity_reserved - $quantity);
            $item->save();
        });
    }

    public function commit(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity) {
            $item->refresh();
            $item->quantity_on_hand = max(0, $item->quantity_on_hand - $quantity);
            $item->quantity_reserved = max(0, $item->quantity_reserved - $quantity);
            $item->save();
        });
    }

    public function restock(InventoryItem $item, int $quantity): void
    {
        $item->increment('quantity_on_hand', $quantity);
    }
}
