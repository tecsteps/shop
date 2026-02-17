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
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'order_number' => $this->order_number,
            'status' => $this->status->value,
            'financial_status' => $this->financial_status->value,
            'fulfillment_status' => $this->fulfillment_status->value,
            'customer' => $this->when($this->relationLoaded('customer') && $this->customer, fn () => [
                'id' => $this->customer->id,
                'name' => $this->customer->first_name.' '.$this->customer->last_name,
                'email' => $this->customer->email,
            ]),
            'email' => $this->email,
            'currency' => $this->currency,
            'subtotal_amount' => $this->subtotal_amount,
            'discount_amount' => $this->discount_amount,
            'shipping_amount' => $this->shipping_amount,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
            'payment_method' => $this->payment_method?->value,
            'billing_address_json' => $this->when($this->billing_address_json !== null, $this->billing_address_json),
            'shipping_address_json' => $this->when($this->shipping_address_json !== null, $this->shipping_address_json),
            'lines' => OrderLineResource::collection($this->whenLoaded('lines')),
            'payments' => $this->when($this->relationLoaded('payments'), fn () => $this->payments->map(fn ($p) => [
                'id' => $p->id,
                'provider' => $p->provider,
                'method' => $p->method->value,
                'provider_payment_id' => $p->provider_payment_id,
                'status' => $p->status->value,
                'amount' => $p->amount,
                'currency' => $p->currency,
                'created_at' => $p->created_at?->toIso8601String(),
            ])),
            'fulfillments' => $this->when($this->relationLoaded('fulfillments'), fn () => $this->fulfillments->map(fn ($f) => [
                'id' => $f->id,
                'status' => $f->status->value,
                'tracking_company' => $f->tracking_company,
                'tracking_number' => $f->tracking_number,
                'tracking_url' => $f->tracking_url,
                'shipped_at' => $f->shipped_at?->toIso8601String(),
            ])),
            'refunds' => $this->when($this->relationLoaded('refunds'), fn () => $this->refunds->map(fn ($r) => [
                'id' => $r->id,
                'amount' => $r->amount,
                'reason' => $r->reason,
                'status' => $r->status->value,
                'created_at' => $r->created_at?->toIso8601String(),
            ])),
            'line_count' => $this->when(
                ! $this->relationLoaded('lines'),
                fn () => $this->lines()->count(),
            ),
            'placed_at' => $this->placed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
