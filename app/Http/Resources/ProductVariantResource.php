<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\ProductVariant
 */
class ProductVariantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'price_amount' => (int) $this->price_amount,
            'compare_at_amount' => $this->compare_at_amount !== null ? (int) $this->compare_at_amount : null,
            'currency' => $this->currency,
            'weight_g' => $this->weight_g,
            'requires_shipping' => (bool) $this->requires_shipping,
            'is_default' => (bool) $this->is_default,
            'position' => (int) $this->position,
            'status' => $this->status?->value,
        ];
    }
}
