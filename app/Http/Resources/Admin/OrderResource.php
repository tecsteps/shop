<?php

namespace App\Http\Resources\Admin;

use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'store_id' => $this->store_id,
            'order_number' => $this->order_number,
            'status' => $this->status->value,
            'financial_status' => $this->financial_status->value,
            'fulfillment_status' => $this->fulfillment_status->value,
            'customer' => $this->customer === null ? null : [
                'id' => $this->customer->getKey(),
                'name' => $this->customer->name,
                'email' => $this->customer->email,
            ],
            'email' => $this->email,
            'currency' => $this->currency,
            'subtotal_amount' => $this->subtotal_amount,
            'discount_amount' => $this->discount_amount,
            'shipping_amount' => $this->shipping_amount,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
            'billing_address_json' => $this->billing_address_json,
            'shipping_address_json' => $this->shipping_address_json,
            'lines' => $this->lines->map(fn (OrderLine $line): array => [
                'id' => $line->getKey(),
                'product_id' => $line->product_id,
                'variant_id' => $line->variant_id,
                'title_snapshot' => $line->title_snapshot,
                'sku_snapshot' => $line->sku_snapshot,
                'quantity' => $line->quantity,
                'unit_price_amount' => $line->unit_price_amount,
                'total_amount' => $line->total_amount,
                'tax_lines_json' => $line->tax_lines_json,
                'discount_allocations_json' => $line->discount_allocations_json,
            ])->all(),
            'payments' => $this->payments->map(fn (Payment $payment): array => [
                'id' => $payment->getKey(),
                'provider' => $payment->provider,
                'method' => $payment->method->value,
                'provider_payment_id' => $payment->provider_payment_id,
                'status' => $payment->status->value,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'created_at' => $payment->created_at?->toIso8601String(),
            ])->all(),
            'fulfillments' => FulfillmentResource::collection($this->fulfillments)->resolve(),
            'refunds' => RefundResource::collection($this->refunds)->resolve(),
            'placed_at' => $this->placed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
