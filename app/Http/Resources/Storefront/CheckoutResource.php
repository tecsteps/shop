<?php

namespace App\Http\Resources\Storefront;

use App\Models\Checkout;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * Serializes a checkout for the storefront API: current state, addresses,
 * computed totals (from `totals_json`), and the available shipping methods for
 * the chosen address. Amounts are integers in minor units (cents).
 *
 * @mixin Checkout
 */
class CheckoutResource extends JsonResource
{
    /**
     * This resource owns the full response body; no `data` wrapping.
     */
    public static $wrap = null;

    /**
     * Available shipping methods, injected by the controller after computing
     * them from the checkout's address.
     *
     * @var Collection<int, \App\Models\ShippingRate>|null
     */
    private ?Collection $availableShippingMethods = null;

    /**
     * Attach the available shipping methods for inclusion in the response.
     *
     * @param  Collection<int, \App\Models\ShippingRate>  $methods
     */
    public function withShippingMethods(Collection $methods): self
    {
        $this->availableShippingMethods = $methods;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $totals = $this->totals_json ?? [];

        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'cart_id' => $this->cart_id,
            'customer_id' => $this->customer_id,
            'status' => $this->status->value,
            'email' => $this->email,
            'payment_method' => $this->payment_method?->value,
            'shipping_address_json' => $this->shipping_address_json,
            'billing_address_json' => $this->billing_address_json,
            'shipping_method_id' => $this->shipping_method_id,
            'discount_code' => $this->discount_code,
            'lines' => CartLineResource::collection($this->cart?->lines ?? new Collection),
            'totals' => [
                'subtotal' => (int) ($totals['subtotal'] ?? 0),
                'discount' => (int) ($totals['discount'] ?? 0),
                'shipping' => (int) ($totals['shipping'] ?? 0),
                'tax' => (int) ($totals['tax'] ?? 0),
                'total' => (int) ($totals['total'] ?? 0),
                'currency' => $this->cart?->currency,
            ],
            'totals_json' => $totals,
            'tax_provider_snapshot_json' => $this->tax_provider_snapshot_json,
            'available_shipping_methods' => $this->shippingMethods(),
            'expires_at' => $this->expires_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    /**
     * Format the available shipping methods, when attached.
     *
     * @return list<array<string, mixed>>
     */
    private function shippingMethods(): array
    {
        if ($this->availableShippingMethods === null) {
            return [];
        }

        return $this->availableShippingMethods->map(function ($rate): array {
            $config = $rate->config_json ?? [];

            return [
                'id' => $rate->id,
                'name' => $rate->name,
                'type' => $rate->type instanceof \BackedEnum ? $rate->type->value : $rate->type,
                'price_amount' => (int) ($config['amount'] ?? $config['price_amount'] ?? 0),
                'currency' => $config['currency'] ?? $this->cart?->currency,
            ];
        })->values()->all();
    }
}
