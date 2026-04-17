<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\OrderLine
 */
class OrderLineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'product_id' => $this->product_id,
            'variant_id' => $this->variant_id,
            'title' => $this->title_snapshot,
            'sku' => $this->sku_snapshot,
            'quantity' => (int) $this->quantity,
            'unit_price_amount' => (int) $this->unit_price_amount,
            'total_amount' => (int) $this->total_amount,
            'tax_lines' => $this->tax_lines_json ?? [],
            'discount_allocations' => $this->discount_allocations_json ?? [],
        ];
    }
}
