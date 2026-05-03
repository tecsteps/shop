<?php

namespace App\Http\Resources\Admin;

use App\Models\ProductMedia;
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
        $this->resource->loadMissing('variants.inventoryItem', 'variants.optionValues.option', 'media', 'options.values', 'collections');

        $featuredImage = $this->resource->media->sortBy('position')->first();

        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'title' => $this->title,
            'handle' => $this->handle,
            'status' => $this->status->value,
            'description_html' => $this->description_html,
            'vendor' => $this->vendor,
            'product_type' => $this->product_type,
            'tags' => $this->tags ?? [],
            'variants_count' => $this->variants_count ?? $this->variants->count(),
            'total_inventory' => $this->variants->sum(fn ($variant): int => (int) ($variant->inventoryItem?->quantity_on_hand ?? 0)),
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'featured_image' => $featuredImage instanceof ProductMedia ? [
                'url' => asset('storage/'.$featuredImage->storage_key),
                'alt_text' => $featuredImage->alt_text,
            ] : null,
            'options' => $this->options
                ->sortBy('position')
                ->map(fn ($option): array => [
                    'id' => $option->id,
                    'name' => $option->name,
                    'position' => $option->position,
                    'values' => $option->values
                        ->sortBy('position')
                        ->map(fn ($value): array => [
                            'id' => $value->id,
                            'value' => $value->value,
                            'position' => $value->position,
                        ])
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
            'variants' => $this->variants
                ->sortBy('position')
                ->map(fn ($variant): array => [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'barcode' => $variant->barcode,
                    'price_amount' => $variant->price_amount,
                    'compare_at_amount' => $variant->compare_at_amount,
                    'currency' => $variant->currency,
                    'weight_g' => $variant->weight_g,
                    'requires_shipping' => $variant->requires_shipping,
                    'is_default' => $variant->is_default,
                    'position' => $variant->position,
                    'status' => $variant->status->value,
                    'option_values' => $variant->optionValues
                        ->sortBy(fn ($value): int => (int) $value->option?->position)
                        ->map(fn ($value): array => [
                            'option_name' => $value->option?->name,
                            'value' => $value->value,
                        ])
                        ->values()
                        ->all(),
                    'inventory' => [
                        'quantity_on_hand' => (int) ($variant->inventoryItem?->quantity_on_hand ?? 0),
                        'quantity_reserved' => (int) ($variant->inventoryItem?->quantity_reserved ?? 0),
                        'policy' => $variant->inventoryItem?->policy->value,
                    ],
                ])
                ->values()
                ->all(),
            'media' => $this->media
                ->sortBy('position')
                ->map(fn (ProductMedia $media): array => [
                    'id' => $media->id,
                    'type' => $media->type->value,
                    'storage_key' => $media->storage_key,
                    'url' => asset('storage/'.$media->storage_key),
                    'alt_text' => $media->alt_text,
                    'width' => $media->width,
                    'height' => $media->height,
                    'mime_type' => $media->mime_type,
                    'byte_size' => $media->byte_size,
                    'position' => $media->position,
                    'status' => $media->status->value,
                ])
                ->values()
                ->all(),
            'collections' => $this->collections
                ->map(fn ($collection): array => [
                    'id' => $collection->id,
                    'title' => $collection->title,
                    'handle' => $collection->handle,
                ])
                ->values()
                ->all(),
        ];
    }
}
