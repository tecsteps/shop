<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'description_html' => $this->when($this->relationLoaded('variants'), $this->description_html),
            'vendor' => $this->vendor,
            'product_type' => $this->product_type,
            'status' => $this->status->value,
            'tags' => $this->tags ?? [],
            'variants_count' => $this->when(
                $this->variants_count !== null,
                $this->variants_count,
                fn () => $this->relationLoaded('variants') ? $this->variants->count() : null,
            ),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
