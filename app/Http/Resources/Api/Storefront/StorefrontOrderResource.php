<?php

namespace App\Http\Resources\Api\Storefront;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StorefrontOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'order_number' => $this->order_number,
            'status' => $this->status->value,
            'financial_status' => $this->financial_status->value,
            'fulfillment_status' => $this->fulfillment_status->value,
            'email' => $this->email,
            'currency' => $this->currency,
            'placed_at' => $this->placed_at?->toIso8601String(),
            'lines' => $this->orderLines->map(fn ($line): array => [
                'title_snapshot' => $line->title_snapshot,
                'variant_title' => $this->variantTitle($line),
                'sku_snapshot' => $line->sku_snapshot,
                'quantity' => $line->quantity,
                'unit_price_amount' => $line->unit_price_amount,
                'total_amount' => $line->total_amount,
            ])->all(),
            'totals' => [
                'subtotal_amount' => $this->subtotal_amount,
                'discount_amount' => $this->discount_amount,
                'shipping_amount' => $this->shipping_amount,
                'tax_amount' => $this->tax_amount,
                'total_amount' => $this->total_amount,
            ],
            'shipping_address' => $this->shipping_address_json,
            'fulfillments' => $this->fulfillments->map(fn ($fulfillment): array => [
                'id' => $fulfillment->id,
                'status' => $fulfillment->status->value,
                'tracking_company' => $fulfillment->tracking_company,
                'tracking_number' => $fulfillment->tracking_number,
                'tracking_url' => $fulfillment->tracking_url,
                'shipped_at' => $fulfillment->shipped_at?->toIso8601String(),
                'line_items' => $fulfillment->fulfillmentLines->map(fn ($fulfillmentLine): array => [
                    'order_line_id' => $fulfillmentLine->order_line_id,
                    'quantity' => $fulfillmentLine->quantity,
                ])->all(),
            ])->all(),
        ];
    }

    protected function variantTitle(mixed $line): ?string
    {
        $variant = $line->variant;

        if ($variant === null) {
            return null;
        }

        $labels = $variant->optionValues->pluck('value')->all();

        return $labels === [] ? 'Default' : implode(' / ', $labels);
    }
}
