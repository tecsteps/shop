<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckoutResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('cart.lines.variant.product');

        $lines = $this->resource->cart?->lines->map(fn ($line) => [
            'variant_id' => $line->variant_id,
            'product_title' => $line->variant?->product?->title,
            'variant_title' => $line->variant?->sku,
            'sku' => $line->variant?->sku,
            'quantity' => $line->quantity,
            'unit_price_amount' => $line->unit_price_amount,
            'line_total_amount' => $line->line_total_amount,
        ]) ?? collect();

        return [
            'id' => $this->resource->id,
            'store_id' => $this->resource->store_id,
            'cart_id' => $this->resource->cart_id,
            'customer_id' => $this->resource->customer_id,
            'status' => $this->resource->status->value,
            'email' => $this->resource->email,
            'shipping_address_json' => $this->resource->shipping_address_json,
            'billing_address_json' => $this->resource->billing_address_json,
            'shipping_method_id' => $this->resource->shipping_method_id,
            'payment_method' => $this->resource->payment_method,
            'discount_code' => $this->resource->discount_code,
            'lines' => $lines->toArray(),
            'totals' => $this->resource->totals_json ?? [],
            'expires_at' => $this->resource->expires_at?->toIso8601String(),
            'created_at' => $this->resource->created_at?->toIso8601String(),
        ];
    }
}
