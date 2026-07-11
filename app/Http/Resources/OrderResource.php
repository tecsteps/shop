<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'order_number' => $this->order_number,
            'status' => $this->status,
            'financial_status' => $this->financial_status,
            'fulfillment_status' => $this->fulfillment_status,
            'payment_method' => $this->payment_method,
            'currency' => $this->currency,
            'subtotal_amount' => $this->subtotal_amount,
            'discount_amount' => $this->discount_amount,
            'shipping_amount' => $this->shipping_amount,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
            'email' => $this->email,
            'placed_at' => $this->placed_at,
            'lines' => $this->whenLoaded('lines'),
            'payments' => $this->whenLoaded('payments'),
            'refunds' => $this->whenLoaded('refunds'),
            'fulfillments' => $this->whenLoaded('fulfillments'),
        ];
    }
}
