<?php

namespace App\Http\Resources\Api\Admin;

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
            'description_html' => $this->description_html,
            'vendor' => $this->vendor,
            'product_type' => $this->product_type,
            'status' => $this->status->value,
            'tags' => $this->tags ?? [],
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'options' => ProductOptionResource::collection($this->whenLoaded('productOptions', $this->productOptions ?? collect())),
            'variants' => VariantResource::collection($this->whenLoaded('productVariants', $this->productVariants ?? collect())),
            'media' => ProductMediaResource::collection($this->whenLoaded('productMedia', $this->productMedia ?? collect())),
            'collections' => $this->whenLoaded('collections', fn () => $this->collections->map(fn ($collection): array => [
                'id' => $collection->id,
                'title' => $collection->title,
                'handle' => $collection->handle,
            ])->all(), []),
        ];
    }
}
