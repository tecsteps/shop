<?php

namespace App\Http\Resources\Admin\V1;

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
            'store_id' => $this->store_id,
            'order_number' => $this->order_number,
            'email' => $this->email,
            'customer' => $this->whenLoaded('customer', fn () => $this->customer ? [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'email' => $this->customer->email,
            ] : null),
            'status' => $this->status?->value,
            'financial_status' => $this->financial_status?->value,
            'fulfillment_status' => $this->fulfillment_status?->value,
            'payment_method' => $this->payment_method?->value,
            'currency' => $this->currency,
            'subtotal_amount' => $this->subtotal_amount,
            'discount_amount' => $this->discount_amount,
            'shipping_amount' => $this->shipping_amount,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
            'lines_count' => $this->whenCounted('lines'),
            'lines' => $this->whenLoaded('lines', fn () => $this->lines->map(fn ($line): array => [
                'id' => $line->id,
                'product_id' => $line->product_id,
                'variant_id' => $line->variant_id,
                'title' => $line->title_snapshot,
                'sku' => $line->sku_snapshot,
                'quantity' => $line->quantity,
                'unit_price_amount' => $line->unit_price_amount,
                'total_amount' => $line->total_amount,
            ])->values()),
            'payments' => $this->whenLoaded('payments', fn () => $this->payments->map(fn ($payment): array => [
                'id' => $payment->id,
                'provider' => $payment->provider,
                'method' => $payment->method?->value,
                'provider_payment_id' => $payment->provider_payment_id,
                'status' => $payment->status?->value,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
            ])->values()),
            'refunds' => RefundResource::collection($this->whenLoaded('refunds')),
            'fulfillments' => FulfillmentResource::collection($this->whenLoaded('fulfillments')),
            'shipping_address' => $this->shipping_address_json,
            'billing_address' => $this->billing_address_json,
            'placed_at' => $this->placed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
