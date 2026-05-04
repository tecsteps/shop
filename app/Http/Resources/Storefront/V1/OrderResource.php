<?php

namespace App\Http\Resources\Storefront\V1;

use App\Enums\PaymentMethod;
use App\Support\OrderAccessToken;
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
            'access_token' => OrderAccessToken::make($this->resource),
            'email' => $this->email,
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
            'shipping_address' => $this->shipping_address_json,
            'billing_address' => $this->billing_address_json,
            'lines' => $this->whenLoaded('lines', fn () => $this->lines->map(fn ($line): array => [
                'id' => $line->id,
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
                'status' => $payment->status?->value,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
            ])->values()),
            'fulfillments' => $this->whenLoaded('fulfillments', fn () => $this->fulfillments->map(fn ($fulfillment): array => [
                'id' => $fulfillment->id,
                'status' => $fulfillment->status?->value,
                'tracking_company' => $fulfillment->tracking_company,
                'tracking_number' => $fulfillment->tracking_number,
                'tracking_url' => $fulfillment->tracking_url,
                'shipped_at' => $fulfillment->shipped_at?->toIso8601String(),
                'delivered_at' => $fulfillment->delivered_at?->toIso8601String(),
            ])->values()),
            'bank_transfer_instructions' => $this->payment_method === PaymentMethod::BankTransfer ? [
                'bank_name' => 'Mock Bank AG',
                'bic' => 'COBADEFFXXX',
                'iban' => 'DE89 3704 0044 0532 0130 00',
                'reference' => $this->order_number,
                'amount' => $this->total_amount,
                'currency' => $this->currency,
            ] : null,
            'placed_at' => $this->placed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
