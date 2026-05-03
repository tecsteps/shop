<?php

namespace App\Http\Resources\Admin;

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
        $this->resource->loadMissing('products');

        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'title' => $this->title,
            'handle' => $this->handle,
            'description_html' => $this->description_html,
            'type' => $this->type,
            'status' => $this->status->value,
            'products_count' => $this->products_count ?? $this->products->count(),
            'products' => $this->products
                ->map(fn ($product): array => [
                    'id' => $product->id,
                    'title' => $product->title,
                    'handle' => $product->handle,
                    'status' => $product->status->value,
                ])
                ->values()
                ->all(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
