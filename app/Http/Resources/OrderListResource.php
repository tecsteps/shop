<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'order_number' => $this->resource->order_number,
            'status' => $this->resource->status->value,
            'financial_status' => $this->resource->financial_status->value,
            'fulfillment_status' => $this->resource->fulfillment_status->value,
            'customer' => $this->resource->customer ? [
                'id' => $this->resource->customer->id,
                'name' => $this->resource->customer->name,
                'email' => $this->resource->customer->email,
            ] : null,
            'currency' => $this->resource->currency,
            'subtotal_amount' => $this->resource->subtotal_amount,
            'discount_amount' => $this->resource->discount_amount,
            'shipping_amount' => $this->resource->shipping_amount,
            'tax_amount' => $this->resource->tax_amount,
            'total_amount' => $this->resource->total_amount,
            'line_count' => $this->resource->lines_count ?? $this->resource->lines->count(),
            'placed_at' => $this->resource->placed_at,
            'created_at' => $this->resource->created_at,
        ];
    }
}
