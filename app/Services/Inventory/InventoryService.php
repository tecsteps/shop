<?php

namespace App\Services\Inventory;

use App\Enums\InventoryPolicy;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function checkAvailability(InventoryItem $item, int $quantity): bool
    {
        return $item->canFulfill($quantity);
    }

    public function reserve(InventoryItem $item, int $quantity): void
    {
        DB::transaction(function () use ($item, $quantity) {
            $item->refresh();
            if ($item->policy === InventoryPolicy::Deny && $item->available() < $quantity) {
                throw new InsufficientInventoryException($item->variant_id, $quantity, $item->available());
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
        DB::transaction(function () use ($item, $quantity) {
            $item->refresh();
            $item->quantity_on_hand += $quantity;
            $item->save();
        });
    }

    public function adjustOnHand(InventoryItem $item, int $newQuantity): void
    {
        DB::transaction(function () use ($item, $newQuantity) {
            $item->refresh();
            $item->quantity_on_hand = max(0, $newQuantity);
            $item->save();
        });
    }
}
