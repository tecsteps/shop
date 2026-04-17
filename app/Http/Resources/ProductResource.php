<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Product
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'title' => $this->title,
            'handle' => $this->handle,
            'status' => $this->status?->value,
            'description_html' => $this->description_html,
            'vendor' => $this->vendor,
            'product_type' => $this->product_type,
            'tags' => $this->tags ?? [],
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'media' => ProductMediaResource::collection($this->whenLoaded('media')),
        ];
    }
}
