<?php

namespace App\Http\Resources\Admin;

use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Collection payload for the Admin API (spec 02 §3.3). The products_count
 * key appears when the query used withCount('products').
 *
 * @mixin Collection
 */
class CollectionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
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
            'type' => $this->type->value,
            'status' => $this->status->value,
            'products_count' => $this->whenCounted('products'),
            'product_ids' => $this->when(
                $this->resource->relationLoaded('products'),
                fn (): array => $this->products->pluck('id')->all(),
            ),
            'created_at' => $this->created_at?->toIso8601ZuluString(),
            'updated_at' => $this->updated_at?->toIso8601ZuluString(),
        ];
    }
}
