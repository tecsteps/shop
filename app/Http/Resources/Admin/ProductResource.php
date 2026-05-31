<?php

namespace App\Http\Resources\Admin;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Full admin product representation: scalar fields plus nested options,
 * variants (with inventory and option values), media, and collections.
 * Wrapped in a `data` key per the admin API conventions.
 *
 * @mixin Product
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
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'options' => $this->whenLoaded('options', fn () => $this->options->map(fn ($option): array => [
                'id' => $option->id,
                'name' => $option->name,
                'position' => $option->position,
                'values' => $option->relationLoaded('values')
                    ? $option->values->map(fn ($value): array => [
                        'id' => $value->id,
                        'value' => $value->value,
                        'position' => $value->position,
                    ])->values()
                    : [],
            ])->values()),
            'variants' => $this->whenLoaded('variants', fn () => $this->variants->map(fn ($variant): array => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'barcode' => $variant->barcode,
                'price_amount' => $variant->price_amount,
                'compare_at_amount' => $variant->compare_at_amount,
                'currency' => $variant->currency,
                'weight_g' => $variant->weight_g,
                'requires_shipping' => (bool) $variant->requires_shipping,
                'is_default' => (bool) $variant->is_default,
                'position' => $variant->position,
                'status' => $variant->status->value,
                'inventory' => $variant->relationLoaded('inventoryItem') && $variant->inventoryItem
                    ? [
                        'quantity_on_hand' => $variant->inventoryItem->quantity_on_hand,
                        'quantity_reserved' => $variant->inventoryItem->quantity_reserved,
                        'policy' => $variant->inventoryItem->policy->value,
                    ]
                    : null,
            ])->values()),
            'media' => $this->whenLoaded('media', fn () => $this->media->map(fn ($media): array => [
                'id' => $media->id,
                'type' => $media->type->value,
                'storage_key' => $media->storage_key,
                'url' => $media->storage_key ? Storage::disk('public')->url($media->storage_key) : null,
                'alt_text' => $media->alt_text,
                'width' => $media->width,
                'height' => $media->height,
                'mime_type' => $media->mime_type,
                'byte_size' => $media->byte_size,
                'position' => $media->position,
                'status' => $media->status->value,
            ])->values()),
            'collections' => $this->whenLoaded('collections', fn () => $this->collections->map(fn ($collection): array => [
                'id' => $collection->id,
                'title' => $collection->title,
                'handle' => $collection->handle,
            ])->values()),
        ];
    }
}
