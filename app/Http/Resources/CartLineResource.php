<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\CartLine
 */
class CartLineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cart_id' => $this->cart_id,
            'variant_id' => $this->variant_id,
            'quantity' => (int) $this->quantity,
            'unit_price_amount' => (int) $this->unit_price_amount,
            'line_subtotal_amount' => (int) $this->line_subtotal_amount,
            'line_discount_amount' => (int) $this->line_discount_amount,
            'line_total_amount' => (int) $this->line_total_amount,
        ];
    }
}
