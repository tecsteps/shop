<?php

namespace App\Http\Resources;

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
        $this->resource->loadMissing('cart.lines.variant.product');

        return [
            'id' => $this->resource->id,
            'store_id' => $this->resource->store_id,
            'cart_id' => $this->resource->cart_id,
            'customer_id' => $this->resource->customer_id,
            'status' => $this->resource->status->value,
            'email' => $this->resource->email,
            'payment_method' => $this->resource->payment_method?->value,
            'shipping_address_json' => $this->resource->shipping_address_json,
            'billing_address_json' => $this->resource->billing_address_json,
            'shipping_method_id' => $this->resource->shipping_method_id,
            'discount_code' => $this->resource->discount_code,
            'totals_json' => $this->resource->totals_json,
            'expires_at' => $this->resource->expires_at,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
