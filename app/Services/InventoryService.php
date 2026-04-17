<?php

namespace App\Services;

use App\Enums\InventoryPolicy;
use App\Exceptions\InsufficientInventoryException;
use App\Models\InventoryItem;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function checkAvailability(ProductVariant $variant, int $quantity): bool
    {
        $item = $this->loadItem($variant);

        if ($item === null) {
            return false;
        }

        if ($item->policy === InventoryPolicy::Continue) {
            return true;
        }

        return $item->available() >= $quantity;
    }

    public function reserve(ProductVariant $variant, int $quantity): InventoryItem
    {
        return DB::transaction(function () use ($variant, $quantity): InventoryItem {
            $item = $this->lockItem($variant);

            if ($item->policy === InventoryPolicy::Deny && $item->available() < $quantity) {
                throw new InsufficientInventoryException(
                    $variant->getKey(),
                    $quantity,
                    $item->available(),
                );
            }

            $item->quantity_reserved += $quantity;
            $item->save();

            return $item;
        });
    }

    public function release(ProductVariant $variant, int $quantity): InventoryItem
    {
        return DB::transaction(function () use ($variant, $quantity): InventoryItem {
            $item = $this->lockItem($variant);

            $item->quantity_reserved = max(0, $item->quantity_reserved - $quantity);
            $item->save();

            return $item;
        });
    }

    public function commit(ProductVariant $variant, int $quantity): InventoryItem
    {
        return DB::transaction(function () use ($variant, $quantity): InventoryItem {
            $item = $this->lockItem($variant);

            $item->quantity_on_hand -= $quantity;
            $item->quantity_reserved = max(0, $item->quantity_reserved - $quantity);
            $item->save();

            return $item;
        });
    }

    public function restock(ProductVariant $variant, int $quantity): InventoryItem
    {
        return DB::transaction(function () use ($variant, $quantity): InventoryItem {
            $item = $this->lockItem($variant);

            $item->quantity_on_hand += $quantity;
            $item->save();

            return $item;
        });
    }

    protected function loadItem(ProductVariant $variant): ?InventoryItem
    {
        return InventoryItem::query()
            ->withoutGlobalScopes()
            ->where('variant_id', $variant->getKey())
            ->first();
    }

    protected function lockItem(ProductVariant $variant): InventoryItem
    {
        $item = InventoryItem::query()
            ->withoutGlobalScopes()
            ->where('variant_id', $variant->getKey())
            ->lockForUpdate()
            ->first();

        if ($item === null) {
            throw new \RuntimeException("Inventory item missing for variant {$variant->getKey()}.");
        }

        return $item;
    }
}
