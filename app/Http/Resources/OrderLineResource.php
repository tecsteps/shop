<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderLineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'variant_id' => $this->variant_id,
            'title_snapshot' => $this->title_snapshot,
            'sku_snapshot' => $this->sku_snapshot,
            'variant_title_snapshot' => $this->variant_title_snapshot,
            'quantity' => $this->quantity,
            'unit_price_amount' => $this->unit_price_amount,
            'subtotal_amount' => $this->subtotal_amount,
            'tax_amount' => $this->tax_amount,
            'discount_amount' => $this->discount_amount,
            'total_amount' => $this->total_amount,
            'requires_shipping' => $this->requires_shipping,
        ];
    }
}
