<?php

namespace App\Http\Resources\Admin;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Product
 */
class ProductListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $featuredImage = $this->media->first();

        return [
            'id' => $this->getKey(),
            'store_id' => $this->store_id,
            'title' => $this->title,
            'handle' => $this->handle,
            'status' => $this->status->value,
            'vendor' => $this->vendor,
            'product_type' => $this->product_type,
            'tags' => $this->tags ?? [],
            'variants_count' => (int) $this->variants_count,
            'total_inventory' => (int) ($this->total_inventory ?? 0),
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'featured_image' => $featuredImage === null ? null : [
                'url' => Storage::disk('public')->url($featuredImage->storage_key),
                'alt_text' => $featuredImage->alt_text,
            ],
        ];
    }
}
