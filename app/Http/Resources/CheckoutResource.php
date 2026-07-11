<?php

namespace App\Http\Resources;

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
            'cart_id' => $this->cart_id,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'email' => $this->email,
            'shipping_address' => $this->shipping_address_json,
            'billing_address' => $this->billing_address_json,
            'shipping_method_id' => $this->shipping_method_id,
            'discount_code' => $this->discount_code,
            'totals' => $this->totals_json,
            'expires_at' => $this->expires_at,
        ];
    }
}
