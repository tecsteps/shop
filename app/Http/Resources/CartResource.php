<?php

namespace App\Http\Resources;

use App\Models\Cart;
use App\Models\CartLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Cart */
class CartResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Cart $cart */
        $cart = $this->resource;
        $cart->loadMissing('lines.variant.product');

        $lines = $cart->lines->map(fn (CartLine $line) => [
            'id' => $line->id,
            'variant_id' => $line->variant_id,
            'product_title' => $line->variant->product->title,
            'variant_title' => $line->variant->sku,
            'sku' => $line->variant->sku,
            'quantity' => $line->quantity,
            'unit_price_amount' => $line->unit_price_amount,
            'line_subtotal_amount' => $line->line_subtotal_amount,
            'line_discount_amount' => $line->line_discount_amount,
            'line_total_amount' => $line->line_total_amount,
        ]);

        $subtotal = $lines->sum('line_subtotal_amount');
        $discount = $lines->sum('line_discount_amount');
        $total = $lines->sum('line_total_amount');

        return [
            'id' => $cart->id,
            'store_id' => $cart->store_id,
            'customer_id' => $cart->customer_id,
            'currency' => $cart->currency,
            'cart_version' => $cart->cart_version,
            'status' => $cart->status->value,
            'lines' => $lines->toArray(),
            'totals' => [
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'currency' => $cart->currency,
                'line_count' => $lines->count(),
                'item_count' => $lines->sum('quantity'),
            ],
            'created_at' => $cart->created_at?->toIso8601String(),
            'updated_at' => $cart->updated_at?->toIso8601String(),
        ];
    }
}
