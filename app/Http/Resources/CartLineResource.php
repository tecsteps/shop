<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartLineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $variant = $this->variant;
        $product = $variant?->product;
        $inventoryItem = $variant?->inventoryItem;

        return [
            'id' => $this->id,
            'variant_id' => $this->variant_id,
            'product_title' => $product?->title,
            'variant_title' => $variant?->sku,
            'sku' => $variant?->sku,
            'quantity' => $this->quantity,
            'unit_price_amount' => $this->unit_price_amount,
            'line_subtotal_amount' => $this->line_subtotal_amount,
            'line_discount_amount' => $this->line_discount_amount,
            'line_total_amount' => $this->line_total_amount,
            'requires_shipping' => $variant?->requires_shipping ?? true,
            'available_quantity' => $inventoryItem
                ? $inventoryItem->quantity_on_hand - $inventoryItem->quantity_reserved
                : null,
        ];
    }
}
