<?php

namespace App\Services;

use App\Enums\InventoryPolicy;
use App\Exceptions\InsufficientInventoryException;
use App\Models\InventoryItem;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function availableQuantity(ProductVariant $variant): int
    {
        $inventory = $this->resolveInventory($variant);

        return $inventory->quantity_on_hand - $inventory->quantity_reserved;
    }

    public function assertCanReserve(ProductVariant $variant, int $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }

        $inventory = $this->resolveInventory($variant);

        if ($inventory->policy !== InventoryPolicy::Deny) {
            return;
        }

        $available = $inventory->quantity_on_hand - $inventory->quantity_reserved;

        if ($available < $quantity) {
            throw new InsufficientInventoryException($variant->id, $quantity, $available);
        }
    }

    public function reserve(ProductVariant $variant, int $quantity): InventoryItem
    {
        if ($quantity <= 0) {
            return $this->resolveInventory($variant);
        }

        return DB::transaction(function () use ($variant, $quantity): InventoryItem {
            $inventory = $this->resolveInventory($variant);

            if ($inventory->policy === InventoryPolicy::Deny) {
                $available = $inventory->quantity_on_hand - $inventory->quantity_reserved;

                if ($available < $quantity) {
                    throw new InsufficientInventoryException($variant->id, $quantity, $available);
                }
            }

            $inventory->quantity_reserved += $quantity;
            $inventory->save();

            return $inventory->fresh();
        });
    }

    public function release(ProductVariant $variant, int $quantity): InventoryItem
    {
        if ($quantity <= 0) {
            return $this->resolveInventory($variant);
        }

        return DB::transaction(function () use ($variant, $quantity): InventoryItem {
            $inventory = $this->resolveInventory($variant);
            $inventory->quantity_reserved = max(0, $inventory->quantity_reserved - $quantity);
            $inventory->save();

            return $inventory->fresh();
        });
    }

    public function commit(ProductVariant $variant, int $quantity): InventoryItem
    {
        if ($quantity <= 0) {
            return $this->resolveInventory($variant);
        }

        return DB::transaction(function () use ($variant, $quantity): InventoryItem {
            $inventory = $this->resolveInventory($variant);
            $inventory->quantity_on_hand -= $quantity;
            $inventory->quantity_reserved = max(0, $inventory->quantity_reserved - $quantity);
            $inventory->save();

            return $inventory->fresh();
        });
    }

    public function restock(ProductVariant $variant, int $quantity): InventoryItem
    {
        if ($quantity <= 0) {
            return $this->resolveInventory($variant);
        }

        return DB::transaction(function () use ($variant, $quantity): InventoryItem {
            $inventory = $this->resolveInventory($variant);
            $inventory->quantity_on_hand += $quantity;
            $inventory->save();

            return $inventory->fresh();
        });
    }

    private function resolveInventory(ProductVariant $variant): InventoryItem
    {
        $variant->loadMissing('product', 'inventoryItem');

        if ($variant->inventoryItem) {
            return $variant->inventoryItem;
        }

        return InventoryItem::query()->create([
            'store_id' => $variant->product->store_id,
            'variant_id' => $variant->id,
            'quantity_on_hand' => 0,
            'quantity_reserved' => 0,
            'policy' => InventoryPolicy::Deny,
        ]);
    }
}
