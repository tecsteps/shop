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

        return $item->quantity_available >= $quantity;
    }

    public function reserve(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity) {
            $item->refresh();

            if ($item->policy === InventoryPolicy::Deny && $item->quantity_available < $quantity) {
                throw new InsufficientInventoryException(
                    "Insufficient inventory: available {$item->quantity_available}, requested {$quantity}."
                );
            }

            $item->increment('quantity_reserved', $quantity);
        });
    }

    public function release(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity) {
            $item->decrement('quantity_reserved', $quantity);
        });
    }

    public function commit(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity) {
            $item->decrement('quantity_on_hand', $quantity);
            $item->decrement('quantity_reserved', $quantity);
        });
    }

    public function restock(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity) {
            $item->increment('quantity_on_hand', $quantity);
        });
    }
}
