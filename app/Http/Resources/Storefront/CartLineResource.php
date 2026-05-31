<?php

namespace App\Http\Resources\Storefront;

use App\Models\CartLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Serializes a single cart line for the storefront API. Amounts are integers in
 * minor units (cents).
 *
 * @mixin CartLine
 */
class CartLineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $variant = $this->variant;
        $product = $variant?->product;
        $inventory = $variant?->inventoryItem;
        $imageKey = $product?->primaryImage()?->storage_key;

        return [
            'id' => $this->id,
            'variant_id' => $this->variant_id,
            'product_title' => $product?->title,
            'variant_title' => $this->variantTitle(),
            'sku' => $variant?->sku,
            'quantity' => $this->quantity,
            'unit_price_amount' => $this->unit_price_amount,
            'line_subtotal_amount' => $this->line_subtotal_amount,
            'line_discount_amount' => $this->line_discount_amount,
            'line_total_amount' => $this->line_total_amount,
            'image_url' => $imageKey ? Storage::disk('public')->url($imageKey) : null,
            'requires_shipping' => (bool) ($variant?->requires_shipping ?? true),
            'available_quantity' => $inventory?->available(),
        ];
    }

    /**
     * The variant's option-value title (e.g. "Blue / Medium"), or null when the
     * variant has no options.
     */
    private function variantTitle(): ?string
    {
        $variant = $this->variant;

        if ($variant === null) {
            return null;
        }

        $options = $variant->relationLoaded('optionValues')
            ? $variant->optionValues->pluck('value')->implode(' / ')
            : $variant->optionValues()->pluck('value')->implode(' / ');

        return $options === '' ? null : $options;
    }
}
