<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'price_amount' => $this->price_amount,
            'compare_at_amount' => $this->compare_at_amount,
            'currency' => $this->currency,
            'weight_g' => $this->weight_g,
            'requires_shipping' => $this->requires_shipping,
            'is_default' => $this->is_default,
            'position' => $this->position,
            'status' => $this->status->value,
            'inventory' => $this->when($this->relationLoaded('inventoryItem') && $this->inventoryItem, fn () => [
                'quantity_on_hand' => $this->inventoryItem->quantity_on_hand,
                'quantity_reserved' => $this->inventoryItem->quantity_reserved,
                'policy' => $this->inventoryItem->policy->value,
            ]),
        ];
    }
}
