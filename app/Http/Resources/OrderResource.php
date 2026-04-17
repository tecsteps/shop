<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Order
 */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'customer_id' => $this->customer_id,
            'order_number' => $this->order_number,
            'payment_method' => $this->payment_method?->value,
            'status' => $this->status?->value,
            'financial_status' => $this->financial_status?->value,
            'fulfillment_status' => $this->fulfillment_status?->value,
            'currency' => $this->currency,
            'subtotal_amount' => (int) $this->subtotal_amount,
            'discount_amount' => (int) $this->discount_amount,
            'shipping_amount' => (int) $this->shipping_amount,
            'tax_amount' => (int) $this->tax_amount,
            'total_amount' => (int) $this->total_amount,
            'email' => $this->email,
            'billing_address' => $this->billing_address_json,
            'shipping_address' => $this->shipping_address_json,
            'placed_at' => $this->placed_at?->toIso8601String(),
            'lines' => OrderLineResource::collection($this->whenLoaded('lines')),
        ];
    }
}
