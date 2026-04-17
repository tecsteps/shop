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
        return $item->quantityAvailable() >= $quantity;
    }

    public function reserve(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity): void {
            $item->refresh();

            $policy = $item->policy instanceof InventoryPolicy
                ? $item->policy
                : InventoryPolicy::from((string) $item->policy);

            if ($policy === InventoryPolicy::Deny && $item->quantityAvailable() < $quantity) {
                throw new InsufficientInventoryException(
                    "Insufficient inventory for variant {$item->variant_id}."
                );
            }

            $item->quantity_reserved = (int) $item->quantity_reserved + $quantity;
            $item->save();
        });
    }

    public function release(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity): void {
            $item->refresh();

            $item->quantity_reserved = max(0, (int) $item->quantity_reserved - $quantity);
            $item->save();
        });
    }

    public function commit(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity): void {
            $item->refresh();

            $item->quantity_on_hand = max(0, (int) $item->quantity_on_hand - $quantity);
            $item->quantity_reserved = max(0, (int) $item->quantity_reserved - $quantity);
            $item->save();
        });
    }

    public function restock(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity): void {
            $item->refresh();

            $item->quantity_on_hand = (int) $item->quantity_on_hand + $quantity;
            $item->save();
        });
    }
}
