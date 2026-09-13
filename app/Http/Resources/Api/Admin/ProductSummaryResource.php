<?php

namespace App\Http\Resources\Api\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin \App\Models\Product
 */
class ProductSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $featured = $this->whenLoaded('productMedia', fn () => $this->productMedia->first());

        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'title' => $this->title,
            'handle' => $this->handle,
            'status' => $this->status->value,
            'vendor' => $this->vendor,
            'product_type' => $this->product_type,
            'tags' => $this->tags ?? [],
            'variants_count' => $this->whenCounted('productVariants'),
            'total_inventory' => $this->whenLoaded('productVariants', fn () => $this->productVariants->sum(
                fn ($variant): int => $variant->inventoryItem?->quantity_on_hand ?? 0
            ), 0),
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'featured_image' => $featured instanceof \App\Models\ProductMedia ? [
                'url' => Storage::disk('public')->url($featured->storage_key),
                'alt_text' => $featured->alt_text,
            ] : null,
        ];
    }
}
