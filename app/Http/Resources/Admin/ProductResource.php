<?php

namespace App\Http\Resources\Admin;

use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductOption;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full product payload for the Admin API (spec 02 §3.2): nested options,
 * variants with option values and inventory, media, and collections.
 *
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing([
            'options.values',
            'variants.optionValues.option',
            'variants.inventoryItem',
            'media',
            'collections',
        ]);

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
            'published_at' => $this->published_at?->toIso8601ZuluString(),
            'created_at' => $this->created_at?->toIso8601ZuluString(),
            'updated_at' => $this->updated_at?->toIso8601ZuluString(),
            'options' => $this->options->map(fn (ProductOption $option): array => [
                'id' => $option->id,
                'name' => $option->name,
                'position' => $option->position,
                'values' => $option->values->map(fn ($value): array => [
                    'id' => $value->id,
                    'value' => $value->value,
                    'position' => $value->position,
                ])->values()->all(),
            ])->values()->all(),
            'variants' => $this->variants->map(fn (ProductVariant $variant): array => [
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
                'option_values' => $variant->optionValues->map(fn ($value): array => [
                    'option_name' => $value->option?->name,
                    'value' => $value->value,
                ])->values()->all(),
                'inventory' => [
                    'quantity_on_hand' => (int) ($variant->inventoryItem?->quantity_on_hand ?? 0),
                    'quantity_reserved' => (int) ($variant->inventoryItem?->quantity_reserved ?? 0),
                    'policy' => $variant->inventoryItem?->policy?->value ?? 'deny',
                ],
            ])->values()->all(),
            'media' => $this->media->map(fn (ProductMedia $media): array => [
                'id' => $media->id,
                'type' => $media->type->value,
                'storage_key' => $media->storage_key,
                'url' => $media->url(),
                'alt_text' => $media->alt_text,
                'width' => $media->width,
                'height' => $media->height,
                'mime_type' => $media->mime_type,
                'byte_size' => $media->byte_size,
                'position' => $media->position,
                'status' => $media->status->value,
            ])->values()->all(),
            'collections' => $this->collections->map(fn (Collection $collection): array => [
                'id' => $collection->id,
                'title' => $collection->title,
                'handle' => $collection->handle,
            ])->values()->all(),
        ];
    }
}
