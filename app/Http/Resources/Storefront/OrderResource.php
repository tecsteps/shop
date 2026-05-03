<?php

namespace App\Http\Resources\Storefront;

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
        $this->resource->loadMissing('lines', 'payments', 'fulfillments.lines');

        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status->value,
            'financial_status' => $this->financial_status->value,
            'fulfillment_status' => $this->fulfillment_status->value,
            'payment_method' => $this->payment_method->value,
            'email' => $this->email,
            'currency' => $this->currency,
            'subtotal_amount' => $this->subtotal_amount,
            'discount_amount' => $this->discount_amount,
            'shipping_amount' => $this->shipping_amount,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
            'placed_at' => $this->placed_at?->toISOString(),
            'lines' => $this->lines->map(fn ($line): array => [
                'id' => $line->id,
                'title_snapshot' => $line->title_snapshot,
                'sku_snapshot' => $line->sku_snapshot,
                'quantity' => $line->quantity,
                'unit_price_amount' => $line->unit_price_amount,
                'total_amount' => $line->total_amount,
                'tax_lines' => $line->tax_lines_json ?? [],
                'discount_allocations' => $line->discount_allocations_json ?? [],
            ])->all(),
            'payments' => $this->payments->map(fn ($payment): array => [
                'id' => $payment->id,
                'provider' => $payment->provider,
                'method' => $payment->method->value,
                'status' => $payment->status->value,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
            ])->all(),
            'fulfillments' => $this->fulfillments->map(fn ($fulfillment): array => [
                'id' => $fulfillment->id,
                'status' => $fulfillment->status->value,
                'tracking_company' => $fulfillment->tracking_company,
                'tracking_number' => $fulfillment->tracking_number,
                'tracking_url' => $fulfillment->tracking_url,
                'shipped_at' => $fulfillment->shipped_at?->toISOString(),
                'delivered_at' => $fulfillment->delivered_at?->toISOString(),
            ])->all(),
        ];
    }
}
