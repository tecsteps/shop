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
        $this->resource->loadMissing(['customer', 'lines', 'payments', 'fulfillments', 'refunds']);

        return [
            'id' => $this->resource->id,
            'store_id' => $this->resource->store_id,
            'order_number' => $this->resource->order_number,
            'status' => $this->resource->status->value,
            'financial_status' => $this->resource->financial_status->value,
            'fulfillment_status' => $this->resource->fulfillment_status->value,
            'customer' => $this->resource->customer ? [
                'id' => $this->resource->customer->id,
                'name' => $this->resource->customer->name,
                'email' => $this->resource->customer->email,
            ] : null,
            'email' => $this->resource->email,
            'currency' => $this->resource->currency,
            'subtotal_amount' => $this->resource->subtotal_amount,
            'discount_amount' => $this->resource->discount_amount,
            'shipping_amount' => $this->resource->shipping_amount,
            'tax_amount' => $this->resource->tax_amount,
            'total_amount' => $this->resource->total_amount,
            'shipping_address_json' => $this->resource->shipping_address_json,
            'billing_address_json' => $this->resource->billing_address_json,
            'lines' => $this->resource->lines->map(fn ($line) => [
                'id' => $line->id,
                'product_id' => $line->product_id,
                'variant_id' => $line->variant_id,
                'title_snapshot' => $line->title_snapshot,
                'sku_snapshot' => $line->sku_snapshot,
                'quantity' => $line->quantity,
                'unit_price_amount' => $line->unit_price_amount,
                'total_amount' => $line->total_amount,
            ]),
            'payments' => $this->resource->payments->map(fn ($payment) => [
                'id' => $payment->id,
                'provider' => $payment->provider,
                'method' => $payment->method,
                'provider_payment_id' => $payment->provider_payment_id,
                'status' => $payment->status->value,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'created_at' => $payment->created_at,
            ]),
            'fulfillments' => $this->resource->fulfillments->map(fn ($f) => [
                'id' => $f->id,
                'status' => $f->status,
                'tracking_company' => $f->tracking_company,
                'tracking_number' => $f->tracking_number,
                'tracking_url' => $f->tracking_url,
                'shipped_at' => $f->shipped_at,
            ]),
            'refunds' => $this->resource->refunds->map(fn ($r) => [
                'id' => $r->id,
                'amount' => $r->amount,
                'reason' => $r->reason,
                'status' => $r->status->value,
                'created_at' => $r->created_at,
            ]),
            'placed_at' => $this->resource->placed_at,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
