<?php

namespace App\Http\Resources\Storefront\V1;

use App\Enums\InventoryPolicy;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SearchProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $variant = $this->relationLoaded('variants')
            ? $this->variants->first()
            : null;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'handle' => $this->handle,
            'vendor' => $this->vendor,
            'product_type' => $this->product_type,
            'price_amount' => $variant?->price_amount,
            'compare_at_amount' => $variant?->compare_at_amount,
            'currency' => $variant?->currency ?? $this->store?->default_currency,
            'image_url' => $this->relationLoaded('media') ? $this->media->first()?->storage_key : null,
            'in_stock' => $variant instanceof ProductVariant ? $this->variantIsInStock($variant) : false,
            'tags' => $this->tags ?? [],
        ];
    }

    private function variantIsInStock(ProductVariant $variant): bool
    {
        $inventory = $variant->inventoryItem;

        if ($inventory === null) {
            return false;
        }

        return $inventory->availableQuantity() > 0
            || $inventory->policy === InventoryPolicy::Continue;
    }
}
