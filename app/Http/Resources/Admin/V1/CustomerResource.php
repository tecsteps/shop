<?php

namespace App\Http\Resources\Admin\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
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
            'store_id' => $this->store_id,
            'email' => $this->email,
            'name' => $this->name,
            'marketing_opt_in' => $this->marketing_opt_in,
            'orders_count' => $this->whenCounted('orders'),
            'total_spent_amount' => (int) ($this->total_spent_amount ?? 0),
            'addresses' => $this->whenLoaded('addresses', fn () => $this->addresses->map(fn ($address): array => [
                'id' => $address->id,
                'label' => $address->label,
                'address' => $address->address_json,
                'is_default' => $address->is_default,
            ])->values()),
            'orders' => $this->whenLoaded('orders', fn () => $this->orders->map(fn ($order): array => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status?->value,
                'financial_status' => $order->financial_status?->value,
                'fulfillment_status' => $order->fulfillment_status?->value,
                'currency' => $order->currency,
                'total_amount' => $order->total_amount,
                'placed_at' => $order->placed_at?->toIso8601String(),
            ])->values()),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
