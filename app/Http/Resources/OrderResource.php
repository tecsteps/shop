<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['customer', 'lines', 'payments', 'fulfillments.lines', 'refunds']);

        return [
            'id' => $this->resource->id,
            'store_id' => $this->resource->store_id,
            'order_number' => $this->resource->order_number,
            'status' => $this->resource->status->value,
            'financial_status' => $this->resource->financial_status->value,
            'fulfillment_status' => $this->resource->fulfillment_status->value,
            'customer' => $this->resource->customer ? [
                'id' => $this->resource->customer->id,
                'name' => ($this->resource->customer->first_name ?? '').' '.($this->resource->customer->last_name ?? ''),
                'email' => $this->resource->customer->email,
            ] : null,
            'email' => $this->resource->email,
            'currency' => $this->resource->currency,
            'subtotal_amount' => $this->resource->subtotal_amount,
            'discount_amount' => $this->resource->discount_amount,
            'shipping_amount' => $this->resource->shipping_amount,
            'tax_amount' => $this->resource->tax_amount,
            'total_amount' => $this->resource->total_amount,
            'billing_address_json' => $this->resource->billing_address_json,
            'shipping_address_json' => $this->resource->shipping_address_json,
            'lines' => $this->resource->lines->map(fn ($line) => [
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
            ]),
            'payments' => $this->resource->payments->map(fn ($payment) => [
                'id' => $payment->id,
                'provider' => $payment->provider,
                'method' => $payment->method?->value ?? $payment->method,
                'provider_payment_id' => $payment->provider_payment_id,
                'status' => $payment->status?->value ?? $payment->status,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'created_at' => $payment->created_at?->toIso8601String(),
            ]),
            'fulfillments' => $this->resource->fulfillments->map(fn ($fulfillment) => [
                'id' => $fulfillment->id,
                'status' => $fulfillment->status?->value ?? $fulfillment->status,
                'tracking_company' => $fulfillment->tracking_company,
                'tracking_number' => $fulfillment->tracking_number,
                'tracking_url' => $fulfillment->tracking_url,
                'shipped_at' => $fulfillment->shipped_at?->toIso8601String(),
                'line_items' => $fulfillment->lines->map(fn ($line) => [
                    'order_line_id' => $line->order_line_id,
                    'quantity' => $line->quantity,
                ]),
            ]),
            'refunds' => $this->resource->refunds->map(fn ($refund) => [
                'id' => $refund->id,
                'amount' => $refund->amount,
                'reason' => $refund->reason,
                'status' => $refund->status?->value ?? $refund->status,
                'created_at' => $refund->created_at?->toIso8601String(),
            ]),
            'placed_at' => $this->resource->placed_at?->toIso8601String(),
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}
