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
        $this->resource->loadMissing(['variants.inventoryItem', 'variants.optionValues', 'options.values', 'media', 'collections']);

        return [
            'id' => $this->resource->id,
            'store_id' => $this->resource->store_id,
            'title' => $this->resource->title,
            'handle' => $this->resource->handle,
            'description_html' => $this->resource->description_html,
            'vendor' => $this->resource->vendor,
            'product_type' => $this->resource->product_type,
            'status' => $this->resource->status->value,
            'tags' => $this->resource->tags,
            'published_at' => $this->resource->published_at,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
            'options' => $this->resource->options->map(fn ($option) => [
                'id' => $option->id,
                'name' => $option->name,
                'position' => $option->position,
                'values' => $option->values->map(fn ($value) => [
                    'id' => $value->id,
                    'value' => $value->value,
                    'position' => $value->position,
                ]),
            ]),
            'variants' => $this->resource->variants->map(fn ($variant) => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'barcode' => $variant->barcode,
                'price_amount' => $variant->price_amount,
                'compare_at_price_amount' => $variant->compare_at_price_amount,
                'weight_grams' => $variant->weight_grams,
                'requires_shipping' => $variant->requires_shipping,
                'is_default' => $variant->is_default,
                'position' => $variant->position,
                'status' => $variant->status->value,
                'inventory' => $variant->inventoryItem ? [
                    'quantity_on_hand' => $variant->inventoryItem->quantity_on_hand,
                    'quantity_reserved' => $variant->inventoryItem->quantity_reserved,
                    'policy' => $variant->inventoryItem->policy->value,
                ] : null,
            ]),
            'media' => $this->resource->media->map(fn ($media) => [
                'id' => $media->id,
                'type' => $media->type->value,
                'storage_key' => $media->storage_key,
                'alt_text' => $media->alt_text,
                'position' => $media->position,
                'status' => $media->status->value,
            ]),
            'collections' => $this->resource->collections->map(fn ($collection) => [
                'id' => $collection->id,
                'title' => $collection->title,
                'handle' => $collection->handle,
            ]),
        ];
    }
}
