<?php

namespace App\Http\Resources;

use App\Services\ShippingCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckoutResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $totals = $this->totals_json ?? [];

        $availableShippingMethods = [];
        if ($this->shipping_address_json) {
            $calculator = app(ShippingCalculator::class);
            $store = $this->store ?? ($this->store_id ? \App\Models\Store::find($this->store_id) : null);
            if ($store) {
                $zone = $calculator->getMatchingZone($store, $this->shipping_address_json);
                if ($zone) {
                    $availableShippingMethods = $zone->rates()
                        ->where('is_active', true)
                        ->get()
                        ->map(fn ($rate) => [
                            'id' => $rate->id,
                            'name' => $rate->name,
                            'type' => $rate->type->value,
                            'price_amount' => $rate->config_json['amount'] ?? 0,
                            'currency' => $store->default_currency,
                        ])
                        ->values()
                        ->toArray();
                }
            }
        }

        return [
            'id' => $this->id,
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
            'totals' => [
                'subtotal' => $totals['subtotal'] ?? 0,
                'discount' => $totals['discount'] ?? 0,
                'shipping' => $totals['shipping'] ?? 0,
                'tax' => $totals['tax_total'] ?? 0,
                'total' => $totals['total'] ?? 0,
                'currency' => $totals['currency'] ?? 'EUR',
            ],
            'available_shipping_methods' => $availableShippingMethods,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
