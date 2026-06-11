<?php

namespace App\Http\Resources\Admin;

use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
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
            'id' => $this->getKey(),
            'store_id' => $this->store_id,
            'title' => $this->title,
            'handle' => $this->handle,
            'description_html' => $this->description_html,
            'vendor' => $this->vendor,
            'product_type' => $this->product_type,
            'status' => $this->status->value,
            'tags' => $this->tags ?? [],
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'options' => $this->options->map(fn (ProductOption $option): array => [
                'id' => $option->getKey(),
                'name' => $option->name,
                'position' => $option->position,
                'values' => $option->values->map(fn (ProductOptionValue $value): array => [
                    'id' => $value->getKey(),
                    'value' => $value->value,
                    'position' => $value->position,
                ])->all(),
            ])->all(),
            'variants' => $this->variants->map(fn (ProductVariant $variant): array => $this->variantToArray($variant))->all(),
            'media' => $this->media->map(fn (ProductMedia $media): array => [
                'id' => $media->getKey(),
                'type' => $media->type->value,
                'storage_key' => $media->storage_key,
                'url' => Storage::disk('public')->url($media->storage_key),
                'alt_text' => $media->alt_text,
                'width' => $media->width,
                'height' => $media->height,
                'mime_type' => $media->mime_type,
                'byte_size' => $media->byte_size,
                'position' => $media->position,
                'status' => $media->status->value,
            ])->all(),
            'collections' => $this->collections->map(fn (Collection $collection): array => [
                'id' => $collection->getKey(),
                'title' => $collection->title,
                'handle' => $collection->handle,
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function variantToArray(ProductVariant $variant): array
    {
        return [
            'id' => $variant->getKey(),
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
            'option_values' => $variant->optionValues->map(fn (ProductOptionValue $value): array => [
                'option_name' => $value->option?->name,
                'value' => $value->value,
            ])->all(),
            'inventory' => $variant->inventoryItem === null ? null : [
                'quantity_on_hand' => $variant->inventoryItem->quantity_on_hand,
                'quantity_reserved' => $variant->inventoryItem->quantity_reserved,
                'policy' => $variant->inventoryItem->policy->value,
            ],
        ];
    }
}
