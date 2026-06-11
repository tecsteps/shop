<?php

namespace App\Http\Resources\Admin;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'order_number' => $this->order_number,
            'status' => $this->status->value,
            'financial_status' => $this->financial_status->value,
            'fulfillment_status' => $this->fulfillment_status->value,
            'customer' => $this->customer === null ? null : [
                'id' => $this->customer->getKey(),
                'name' => $this->customer->name,
                'email' => $this->customer->email,
            ],
            'currency' => $this->currency,
            'subtotal_amount' => $this->subtotal_amount,
            'discount_amount' => $this->discount_amount,
            'shipping_amount' => $this->shipping_amount,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
            'line_count' => (int) $this->lines_count,
            'placed_at' => $this->placed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
