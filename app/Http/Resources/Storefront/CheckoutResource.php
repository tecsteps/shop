<?php

namespace App\Http\Resources\Storefront;

use App\Services\ShippingCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckoutResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('store', 'cart.lines.variant.product.media', 'cart.lines.variant.optionValues.option', 'cart.lines.variant.inventoryItem', 'shippingRate');
        $shippingAddress = $this->shipping_address_json ?? [];
        $shippingMethods = $shippingAddress === []
            ? collect()
            : app(ShippingCalculator::class)->getAvailableRateQuotes($this->store, $this->cart, $shippingAddress);

        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'cart_id' => $this->cart_id,
            'customer_id' => $this->customer_id,
            'status' => $this->status->value,
            'payment_method' => $this->payment_method?->value,
            'email' => $this->email,
            'shipping_address_json' => $this->shipping_address_json,
            'billing_address_json' => $this->billing_address_json,
            'shipping_method_id' => $this->shipping_method_id,
            'discount_code' => $this->discount_code,
            'lines' => CartLineResource::collection($this->cart->lines),
            'totals' => $this->totals_json ?? [
                'subtotal' => 0,
                'discount' => 0,
                'shipping' => 0,
                'tax' => 0,
                'total' => 0,
                'currency' => $this->cart->currency,
            ],
            'available_shipping_methods' => $shippingMethods->map->toArray()->all(),
            'tax_provider_snapshot_json' => $this->tax_provider_snapshot_json,
            'expires_at' => $this->expires_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
