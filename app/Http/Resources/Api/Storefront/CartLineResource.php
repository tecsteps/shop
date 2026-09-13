<?php

namespace App\Http\Resources\Api\Storefront;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CartLineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $variant = $this->variant;
        $product = $variant->product;
        $labels = $variant->optionValues->pluck('value')->all();
        $inventory = $variant->inventoryItem;

        $media = $product->productMedia->sortBy('position')->first();

        return [
            'id' => $this->id,
            'variant_id' => $this->variant_id,
            'product_title' => $product->title,
            'variant_title' => $labels === [] ? 'Default' : implode(' / ', $labels),
            'sku' => $variant->sku,
            'quantity' => $this->quantity,
            'unit_price_amount' => $this->unit_price_amount,
            'line_subtotal_amount' => $this->line_subtotal_amount,
            'line_discount_amount' => $this->line_discount_amount,
            'line_total_amount' => $this->line_total_amount,
            'image_url' => $media !== null ? Storage::disk('public')->url($media->storage_key) : null,
            'requires_shipping' => (bool) $variant->requires_shipping,
            'available_quantity' => $inventory !== null
                ? max(0, $inventory->quantity_on_hand - $inventory->quantity_reserved)
                : 0,
        ];
    }
}
