<?php

namespace App\Services\Shop;

use App\Models\InventoryItem;
use App\Models\ProductVariant;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function assertPurchasable(ProductVariant $variant, int $quantity): void
    {
        $inventory = $variant->inventoryItem;

        if (! $inventory) {
            return;
        }

        if ($inventory->policy === 'continue') {
            return;
        }

        if ($inventory->availableForSale() < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => 'This product does not have enough stock available.',
            ]);
        }
    }

    public function reserve(ProductVariant $variant, int $quantity): void
    {
        $inventory = $this->lock($variant);

        if (! $inventory || $inventory->policy === 'continue') {
            return;
        }

        if ($inventory->availableForSale() < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => 'This product does not have enough stock available.',
            ]);
        }

        $inventory->increment('quantity_reserved', $quantity);
    }

    public function commit(ProductVariant $variant, int $quantity): void
    {
        $inventory = $this->lock($variant);

        if (! $inventory) {
            return;
        }

        $inventory->decrement('quantity_available', $quantity);

        if ($inventory->quantity_reserved > 0) {
            $inventory->decrement('quantity_reserved', min($inventory->quantity_reserved, $quantity));
        }
    }

    public function restock(ProductVariant $variant, int $quantity): void
    {
        $variant->inventoryItem?->increment('quantity_available', $quantity);
    }

    private function lock(ProductVariant $variant): ?InventoryItem
    {
        return InventoryItem::query()
            ->where('variant_id', $variant->id)
            ->lockForUpdate()
            ->first();
    }
}

