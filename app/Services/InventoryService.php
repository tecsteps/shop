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

        $available = $item->quantity_on_hand - $item->quantity_reserved;

        return $available >= $quantity;
    }

    public function reserve(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity) {
            $item->refresh();

            if ($item->policy === InventoryPolicy::Deny) {
                $available = $item->quantity_on_hand - $item->quantity_reserved;

                if ($available < $quantity) {
                    throw new InsufficientInventoryException(
                        requested: $quantity,
                        available: $available,
                    );
                }
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
