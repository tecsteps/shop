<?php

namespace App\Http\Resources\Admin\V1;

use App\Models\ProductMedia;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
            'store_id' => $this->store_id,
            'title' => $this->title,
            'handle' => $this->handle,
            'description_html' => $this->when($this->relationLoaded('options'), $this->description_html),
            'status' => $this->status?->value,
            'vendor' => $this->vendor,
            'product_type' => $this->product_type,
            'tags' => $this->tags ?? [],
            'variants_count' => $this->whenCounted('variants'),
            'total_inventory' => $this->when($this->relationLoaded('variants'), fn (): int => $this->variants->sum(
                fn (ProductVariant $variant): int => $variant->inventoryItem?->quantity_on_hand ?? 0,
            )),
            'featured_image' => $this->when($this->relationLoaded('media'), function (): ?array {
                $media = $this->media->first();

                return $media instanceof ProductMedia ? $this->mediaPayload($media) : null;
            }),
            'options' => $this->whenLoaded('options', fn () => $this->options->map(fn ($option): array => [
                'id' => $option->id,
                'name' => $option->name,
                'position' => $option->position,
                'values' => $option->values->map(fn ($value): array => [
                    'id' => $value->id,
                    'value' => $value->value,
                    'position' => $value->position,
                ])->values(),
            ])->values()),
            'variants' => $this->when(
                $this->relationLoaded('variants') && $this->relationLoaded('options'),
                fn () => $this->variants->map(fn (ProductVariant $variant): array => [
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
                    'status' => $variant->status?->value,
                    'option_values' => $variant->optionValues->map(fn ($value): array => [
                        'option_name' => $value->option?->name,
                        'value' => $value->value,
                    ])->values(),
                    'inventory' => [
                        'quantity_on_hand' => $variant->inventoryItem?->quantity_on_hand ?? 0,
                        'quantity_reserved' => $variant->inventoryItem?->quantity_reserved ?? 0,
                        'policy' => $variant->inventoryItem?->policy?->value,
                    ],
                ])->values(),
            ),
            'media' => $this->whenLoaded('media', fn () => $this->media->map(fn (ProductMedia $media): array => $this->mediaPayload($media))->values()),
            'collections' => $this->whenLoaded('collections', fn () => $this->collections->map(fn ($collection): array => [
                'id' => $collection->id,
                'title' => $collection->title,
                'handle' => $collection->handle,
            ])->values()),
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mediaPayload(ProductMedia $media): array
    {
        return [
            'id' => $media->id,
            'type' => $media->type?->value,
            'storage_key' => $media->storage_key,
            'url' => Storage::disk('public')->url($media->storage_key),
            'alt_text' => $media->alt_text,
            'width' => $media->width,
            'height' => $media->height,
            'mime_type' => $media->mime_type,
            'byte_size' => $media->byte_size,
            'position' => $media->position,
            'status' => $media->status?->value,
        ];
    }
}
