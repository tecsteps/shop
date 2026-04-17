<?php

namespace App\Http\Resources;

use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Product $product */
        $product = $this->resource;
        $product->loadMissing(['variants.inventoryItem', 'options.values', 'media', 'collections']);

        return [
            'id' => $product->id,
            'store_id' => $product->store_id,
            'title' => $product->title,
            'handle' => $product->handle,
            'description_html' => $product->description_html,
            'vendor' => $product->vendor,
            'product_type' => $product->product_type,
            'status' => $product->status->value,
            'tags' => $product->tags ?? [],
            'published_at' => $product->published_at?->toIso8601String(),
            'created_at' => $product->created_at?->toIso8601String(),
            'updated_at' => $product->updated_at?->toIso8601String(),
            'options' => $product->options->map(fn (ProductOption $option) => [
                'id' => $option->id,
                'name' => $option->name,
                'position' => $option->position,
                'values' => $option->values->map(fn (ProductOptionValue $value) => [
                    'id' => $value->id,
                    'value' => $value->value,
                    'position' => $value->position,
                ]),
            ]),
            'variants' => $product->variants->map(fn (ProductVariant $variant) => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'price_amount' => $variant->price_amount,
                'compare_at_amount' => $variant->compare_at_amount,
                'currency' => $variant->currency ?? $product->store->default_currency ?? 'EUR',
                'weight_g' => $variant->weight_g,
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
            'media' => $product->media->map(fn (ProductMedia $media) => [
                'id' => $media->id,
                'type' => $media->type->value,
                'storage_key' => $media->storage_key,
                'alt_text' => $media->alt_text,
                'position' => $media->position,
                'status' => $media->status->value,
            ]),
            'collections' => $product->collections->map(fn (Collection $collection) => [
                'id' => $collection->id,
                'title' => $collection->title,
                'handle' => $collection->handle,
            ]),
        ];
    }
}
