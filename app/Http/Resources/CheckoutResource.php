<?php

namespace App\Http\Resources;

use App\Models\CartLine;
use App\Models\Checkout;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Checkout */
class CheckoutResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Checkout $checkout */
        $checkout = $this->resource;
        $checkout->loadMissing('cart.lines.variant.product');

        $lines = $checkout->cart->lines->map(fn (CartLine $line) => [
            'variant_id' => $line->variant_id,
            'product_title' => $line->variant->product->title,
            'variant_title' => $line->variant->sku,
            'sku' => $line->variant->sku,
            'quantity' => $line->quantity,
            'unit_price_amount' => $line->unit_price_amount,
            'line_total_amount' => $line->line_total_amount,
        ]);

        return [
            'id' => $checkout->id,
            'store_id' => $checkout->store_id,
            'cart_id' => $checkout->cart_id,
            'customer_id' => $checkout->customer_id,
            'status' => $checkout->status->value,
            'email' => $checkout->email,
            'shipping_address_json' => $checkout->shipping_address_json,
            'billing_address_json' => $checkout->billing_address_json,
            'shipping_method_id' => $checkout->shipping_method_id,
            'payment_method' => $checkout->payment_method,
            'discount_code' => $checkout->discount_code,
            'lines' => $lines->toArray(),
            'totals' => $checkout->totals_json ?? [],
            'expires_at' => $checkout->expires_at?->toIso8601String(),
            'created_at' => $checkout->created_at?->toIso8601String(),
        ];
    }
}
