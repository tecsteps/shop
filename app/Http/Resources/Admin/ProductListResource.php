<?php

namespace App\Http\Resources\Admin;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Product summary for the Admin API list endpoint (spec 02 §3.2).
 * Expects variants (with inventoryItem) and media to be eager-loaded.
 *
 * @mixin Product
 */
class ProductListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $featuredImage = $this->media->first();

        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'title' => $this->title,
            'handle' => $this->handle,
            'status' => $this->status->value,
            'vendor' => $this->vendor,
            'product_type' => $this->product_type,
            'tags' => $this->tags ?? [],
            'variants_count' => $this->variants->count(),
            'total_inventory' => $this->variants->sum(fn ($variant): int => (int) ($variant->inventoryItem?->quantity_on_hand ?? 0)),
            'published_at' => $this->published_at?->toIso8601ZuluString(),
            'created_at' => $this->created_at?->toIso8601ZuluString(),
            'updated_at' => $this->updated_at?->toIso8601ZuluString(),
            'featured_image' => $featuredImage !== null ? [
                'url' => $featuredImage->url(),
                'alt_text' => $featuredImage->alt_text,
            ] : null,
        ];
    }
}
