<?php

namespace App\Http\Resources\Admin;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Compact admin product representation for list endpoints: summary fields plus
 * variant/inventory counts and the featured image.
 *
 * @mixin Product
 */
class ProductListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $featured = $this->primaryImage();

        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'title' => $this->title,
            'handle' => $this->handle,
            'status' => $this->status->value,
            'vendor' => $this->vendor,
            'product_type' => $this->product_type,
            'tags' => $this->tags ?? [],
            'variants_count' => $this->whenCounted('variants', default: $this->variants_count ?? 0),
            'total_inventory' => $this->totalInventory(),
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'featured_image' => $featured ? [
                'url' => $featured->storage_key ? Storage::disk('public')->url($featured->storage_key) : null,
                'alt_text' => $featured->alt_text,
            ] : null,
        ];
    }

    /**
     * Total on-hand inventory across loaded variants, or null when inventory is
     * not eager-loaded.
     */
    private function totalInventory(): ?int
    {
        if (! $this->relationLoaded('variants')) {
            return null;
        }

        return (int) $this->variants->sum(
            fn ($variant): int => $variant->relationLoaded('inventoryItem') && $variant->inventoryItem
                ? $variant->inventoryItem->quantity_on_hand
                : 0,
        );
    }
}
