<?php

namespace App\Http\Resources\Admin\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'type' => $this->type?->value,
            'status' => $this->status?->value,
            'products_count' => $this->whenCounted('products'),
            'products' => $this->whenLoaded('products', fn () => $this->products->map(fn ($product): array => [
                'id' => $product->id,
                'title' => $product->title,
                'handle' => $product->handle,
                'position' => $product->pivot?->position,
            ])->values()),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
