<?php

namespace App\Http\Resources;

use App\Models\Cart;
use App\Services\PricingEngine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public static $wrap = null;

    protected ?PricingEngine $pricing = null;

    public static function fromCart(Cart $cart, PricingEngine $pricing): self
    {
        $resource = new self($cart);
        $resource->pricing = $pricing;

        return $resource;
    }

    public function toArray(Request $request): array
    {
        /** @var Cart $cart */
        $cart = $this->resource;
        $cart->loadMissing('lines.variant.product.media');

        $store = $cart->store()->first();
        $result = $this->pricing
            ? $this->pricing->calculateForCart($cart, $store)
            : null;

        return [
            'id' => $cart->id,
            'store_id' => $cart->store_id,
            'customer_id' => $cart->customer_id,
            'currency' => $cart->currency,
            'cart_version' => $cart->cart_version,
            'status' => $cart->status->value,
            'lines' => $cart->lines->map(fn ($line): array => [
                'id' => $line->id,
                'variant_id' => $line->variant_id,
                'product_title' => $line->variant?->product?->title,
                'sku' => $line->variant?->sku,
                'quantity' => $line->quantity,
                'unit_price_amount' => $line->unit_price_amount,
                'line_subtotal_amount' => $line->line_subtotal_amount,
                'line_discount_amount' => $line->line_discount_amount,
                'line_total_amount' => $line->line_total_amount,
                'requires_shipping' => (bool) ($line->variant?->requires_shipping ?? false),
            ])->all(),
            'totals' => [
                'subtotal' => (int) $cart->lines->sum('line_subtotal_amount'),
                'discount' => $result?->discount ?? 0,
                'total' => $result?->total ?? (int) $cart->lines->sum('line_total_amount'),
                'currency' => $cart->currency,
                'line_count' => $cart->lines->count(),
                'item_count' => (int) $cart->lines->sum('quantity'),
            ],
            'created_at' => $cart->created_at?->toIso8601String(),
            'updated_at' => $cart->updated_at?->toIso8601String(),
        ];
    }
}
