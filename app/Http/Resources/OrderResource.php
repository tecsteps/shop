<?php

namespace App\Http\Resources;

use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Order $order */
        $order = $this->resource;
        $order->loadMissing(['customer', 'lines', 'payments', 'fulfillments.lines', 'refunds']);

        return [
            'id' => $order->id,
            'store_id' => $order->store_id,
            'order_number' => $order->order_number,
            'status' => $order->status->value,
            'financial_status' => $order->financial_status->value,
            'fulfillment_status' => $order->fulfillment_status->value,
            'customer' => $order->customer ? [
                'id' => $order->customer->id,
                'name' => $order->customer->name,
                'email' => $order->customer->email,
            ] : null,
            'email' => $order->email,
            'currency' => $order->currency,
            'subtotal_amount' => $order->subtotal_amount,
            'discount_amount' => $order->discount_amount,
            'shipping_amount' => $order->shipping_amount,
            'tax_amount' => $order->tax_amount,
            'total_amount' => $order->total_amount,
            'billing_address_json' => $order->billing_address_json,
            'shipping_address_json' => $order->shipping_address_json,
            'lines' => $order->lines->map(fn (OrderLine $line) => [
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
            'payments' => $order->payments->map(fn (Payment $payment) => [
                'id' => $payment->id,
                'provider' => $payment->provider,
                'method' => $payment->method->value,
                'provider_payment_id' => $payment->provider_payment_id,
                'status' => $payment->status->value,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'created_at' => $payment->created_at?->toIso8601String(),
            ]),
            'fulfillments' => $order->fulfillments->map(fn (Fulfillment $fulfillment) => [
                'id' => $fulfillment->id,
                'status' => $fulfillment->status->value,
                'tracking_company' => $fulfillment->tracking_company,
                'tracking_number' => $fulfillment->tracking_number,
                'tracking_url' => $fulfillment->tracking_url,
                'shipped_at' => $fulfillment->shipped_at?->toIso8601String(),
                'line_items' => $fulfillment->lines->map(fn (FulfillmentLine $line) => [
                    'order_line_id' => $line->order_line_id,
                    'quantity' => $line->quantity,
                ]),
            ]),
            'refunds' => $order->refunds->map(fn (Refund $refund) => [
                'id' => $refund->id,
                'amount' => $refund->amount,
                'reason' => $refund->reason,
                'status' => $refund->status->value,
                'created_at' => $refund->created_at?->toIso8601String(),
            ]),
            'placed_at' => $order->placed_at?->toIso8601String(),
            'created_at' => $order->created_at?->toIso8601String(),
            'updated_at' => $order->updated_at?->toIso8601String(),
        ];
    }
}
