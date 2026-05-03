<?php

namespace App\Http\Resources\Storefront;

use App\Models\ProductMedia;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductSearchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('variants.inventoryItem', 'media');

        /** @var ProductVariant|null $variant */
        $variant = $this->resource->variants
            ->sortBy([
                ['is_default', 'desc'],
                ['position', 'asc'],
            ])
            ->first();
        $media = $this->resource->media->sortBy('position')->first();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'handle' => $this->handle,
            'url' => route('storefront.products.show', $this->handle),
            'vendor' => $this->vendor,
            'product_type' => $this->product_type,
            'price_amount' => $variant?->price_amount,
            'compare_at_amount' => $variant?->compare_at_amount,
            'currency' => $variant?->currency,
            'image_url' => $media instanceof ProductMedia ? asset('storage/'.$media->storage_key) : null,
            'in_stock' => $this->resource->variants->contains(fn (ProductVariant $variant): bool => ($variant->inventoryItem?->availableQuantity() ?? 0) > 0),
            'tags' => $this->tags ?? [],
        ];
    }
}
