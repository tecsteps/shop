<?php

namespace App\Http\Resources\Storefront;

use App\Models\ProductMedia;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartLineResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ProductVariant|null $variant */
        $variant = $this->resource->relationLoaded('variant') ? $this->resource->variant : null;
        $product = $variant instanceof ProductVariant ? $variant->product : null;
        $media = $product?->relationLoaded('media') ? $product->media->first() : null;

        return [
            'id' => $this->id,
            'variant_id' => $this->variant_id,
            'product_title' => $product?->title,
            'variant_title' => $variant instanceof ProductVariant
                ? ($variant->optionValues->pluck('value')->join(' / ') ?: 'Default')
                : null,
            'sku' => $variant?->sku,
            'quantity' => $this->quantity,
            'unit_price_amount' => $this->unit_price_amount,
            'line_subtotal_amount' => $this->line_subtotal_amount,
            'line_discount_amount' => $this->line_discount_amount,
            'line_total_amount' => $this->line_total_amount,
            'image_url' => $media instanceof ProductMedia ? asset('storage/'.$media->storage_key) : null,
            'requires_shipping' => (bool) $variant?->requires_shipping,
            'available_quantity' => $variant?->inventoryItem?->availableQuantity(),
        ];
    }
}
