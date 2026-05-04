<?php

namespace App\Http\Resources\Storefront\V1;

use App\Support\CheckoutAccessToken;
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
        return [
            'id' => $this->id,
            'access_token' => CheckoutAccessToken::make($this->resource),
            'store_id' => $this->store_id,
            'cart_id' => $this->cart_id,
            'customer_id' => $this->customer_id,
            'status' => $this->status?->value,
            'payment_method' => $this->payment_method,
            'email' => $this->email,
            'shipping_address' => $this->shipping_address_json,
            'billing_address' => $this->billing_address_json,
            'shipping_method_id' => $this->shipping_method_id,
            'discount_code' => $this->discount_code,
            'totals' => $this->totals_json,
            'tax_provider_snapshot' => $this->tax_provider_snapshot_json,
            'available_shipping_rates' => ShippingRateResource::collection($this->whenLoaded('availableRates')),
            'cart' => new CartResource($this->whenLoaded('cart')),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
