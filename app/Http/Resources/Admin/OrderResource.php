<?php

namespace App\Http\Resources\Admin;

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
        $this->resource->loadMissing('customer', 'lines', 'payments', 'fulfillments.lines.orderLine', 'refunds.payment');

        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'order_number' => $this->order_number,
            'status' => $this->status->value,
            'financial_status' => $this->financial_status->value,
            'fulfillment_status' => $this->fulfillment_status->value,
            'payment_method' => $this->payment_method?->value,
            'customer' => $this->customer ? [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'email' => $this->customer->email,
            ] : null,
            'email' => $this->email,
            'currency' => $this->currency,
            'subtotal_amount' => $this->subtotal_amount,
            'discount_amount' => $this->discount_amount,
            'shipping_amount' => $this->shipping_amount,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
            'line_count' => $this->lines->count(),
            'billing_address_json' => $this->billing_address_json,
            'shipping_address_json' => $this->shipping_address_json,
            'lines' => $this->lines
                ->map(fn ($line): array => [
                    'id' => $line->id,
                    'product_id' => $line->product_id,
                    'variant_id' => $line->variant_id,
                    'title_snapshot' => $line->title_snapshot,
                    'sku_snapshot' => $line->sku_snapshot,
                    'quantity' => $line->quantity,
                    'unit_price_amount' => $line->unit_price_amount,
                    'total_amount' => $line->total_amount,
                    'tax_lines_json' => $line->tax_lines_json,
                    'discount_allocations_json' => $line->discount_allocations_json,
                ])
                ->values()
                ->all(),
            'payments' => $this->payments
                ->map(fn ($payment): array => [
                    'id' => $payment->id,
                    'provider' => $payment->provider,
                    'method' => $payment->method?->value,
                    'provider_payment_id' => $payment->provider_payment_id,
                    'status' => $payment->status->value,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'created_at' => $payment->created_at?->toISOString(),
                ])
                ->values()
                ->all(),
            'fulfillments' => FulfillmentResource::collection($this->fulfillments)->resolve(),
            'refunds' => RefundResource::collection($this->refunds)->resolve(),
            'placed_at' => $this->placed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
