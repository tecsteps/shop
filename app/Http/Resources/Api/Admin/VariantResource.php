<?php

namespace App\Http\Resources\Api\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\ProductVariant
 */
class VariantResource extends JsonResource
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
            'requires_shipping' => (bool) $this->requires_shipping,
            'is_default' => (bool) $this->is_default,
            'position' => $this->position,
            'status' => $this->status->value,
            'option_values' => $this->whenLoaded('optionValues', fn () => $this->optionValues->map(fn ($value): array => [
                'option_name' => $value->productOption?->name,
                'value' => $value->value,
            ])->all(), []),
            'inventory' => $this->whenLoaded('inventoryItem', fn () => $this->inventoryItem === null ? null : [
                'quantity_on_hand' => $this->inventoryItem->quantity_on_hand,
                'quantity_reserved' => $this->inventoryItem->quantity_reserved,
                'available' => $this->inventoryItem->quantity_on_hand - $this->inventoryItem->quantity_reserved,
                'policy' => $this->inventoryItem->policy->value,
            ]),
        ];
    }
}
