<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'title' => $this->title,
            'handle' => $this->handle,
            'status' => $this->status,
            'description_html' => $this->description_html,
            'vendor' => $this->vendor,
            'product_type' => $this->product_type,
            'tags' => $this->tags,
            'published_at' => $this->published_at,
            'variants' => $this->whenLoaded('variants', fn () => $this->variants->map(fn ($variant) => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'price_amount' => $variant->price_amount,
                'compare_at_amount' => $variant->compare_at_amount,
                'currency' => $variant->currency,
                'requires_shipping' => $variant->requires_shipping,
                'status' => $variant->status,
            ])),
        ];
    }
}
