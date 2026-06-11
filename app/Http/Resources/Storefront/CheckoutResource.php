<?php

namespace App\Http\Resources\Storefront;

use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\ShippingRate;
use App\Services\ShippingCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Checkout
 */
class CheckoutResource extends JsonResource
{
    /**
     * Storefront checkout responses are not wrapped in a "data" key
     * (spec 02 section 2.2).
     *
     * @var string|null
     */
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $cart = Cart::query()->withoutGlobalScopes()->find($this->cart_id);
        $lines = $cart?->lines()->with(['variant.product', 'variant.optionValues'])->get() ?? collect();
        $totals = $this->totals_json ?? [];

        return [
            'id' => $this->getKey(),
            'store_id' => $this->store_id,
            'cart_id' => $this->cart_id,
            'customer_id' => $this->customer_id,
            'status' => $this->status->value,
            'email' => $this->email,
            'shipping_address_json' => $this->shipping_address_json,
            'billing_address_json' => $this->billing_address_json,
            'shipping_method_id' => $this->shipping_method_id,
            'payment_method' => $this->payment_method,
            'discount_code' => $this->discount_code,
            'lines' => $lines->map(fn (CartLine $line): array => [
                'variant_id' => $line->variant_id,
                'product_title' => $line->variant?->product?->title,
                'variant_title' => $line->variant?->optionValues->pluck('value')->implode(' / ') ?: null,
                'sku' => $line->variant?->sku,
                'quantity' => $line->quantity,
                'unit_price_amount' => $line->unit_price_amount,
                'line_total_amount' => $line->line_total_amount,
            ])->all(),
            'totals' => [
                'subtotal' => (int) ($totals['subtotal'] ?? 0),
                'discount' => (int) ($totals['discount'] ?? 0),
                'shipping' => (int) ($totals['shipping'] ?? 0),
                'tax' => (int) ($totals['tax'] ?? 0),
                'total' => (int) ($totals['total'] ?? 0),
                'currency' => $totals['currency'] ?? $cart?->currency,
            ],
            'tax_provider_snapshot_json' => $this->tax_provider_snapshot_json,
            'available_shipping_methods' => $this->availableShippingMethods(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * Shipping rates available for the checkout's shipping address, empty
     * until an address has been provided.
     *
     * @return list<array<string, mixed>>
     */
    protected function availableShippingMethods(): array
    {
        $address = $this->shipping_address_json;

        if (blank($address)) {
            return [];
        }

        return app(ShippingCalculator::class)
            ->getAvailableRates($this->store, $address)
            ->map(fn (ShippingRate $rate): array => [
                'id' => $rate->getKey(),
                'name' => $rate->name,
                'type' => $rate->type->value,
                'price_amount' => (int) ($rate->config_json['amount'] ?? 0),
                'currency' => $this->store->default_currency,
            ])
            ->all();
    }
}
