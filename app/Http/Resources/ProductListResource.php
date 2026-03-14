<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'store_id' => $this->resource->store_id,
            'title' => $this->resource->title,
            'handle' => $this->resource->handle,
            'status' => $this->resource->status->value,
            'vendor' => $this->resource->vendor,
            'product_type' => $this->resource->product_type,
            'tags' => $this->resource->tags,
            'variants_count' => $this->resource->variants_count ?? $this->resource->variants->count(),
            'published_at' => $this->resource->published_at,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
