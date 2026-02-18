<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('lines.variant.product');

        $lines = $this->resource->lines->map(fn ($line) => [
            'id' => $line->id,
            'variant_id' => $line->variant_id,
            'product_title' => $line->variant?->product?->title,
            'variant_title' => $line->variant?->sku,
            'sku' => $line->variant?->sku,
            'quantity' => $line->quantity,
            'unit_price_amount' => $line->unit_price_amount,
            'line_subtotal_amount' => $line->line_subtotal_amount,
            'line_discount_amount' => $line->line_discount_amount,
            'line_total_amount' => $line->line_total_amount,
        ]);

        $subtotal = $lines->sum('line_subtotal_amount');
        $discount = $lines->sum('line_discount_amount');
        $total = $lines->sum('line_total_amount');

        return [
            'id' => $this->resource->id,
            'store_id' => $this->resource->store_id,
            'customer_id' => $this->resource->customer_id,
            'currency' => $this->resource->currency,
            'cart_version' => $this->resource->cart_version,
            'status' => $this->resource->status->value,
            'lines' => $lines->toArray(),
            'totals' => [
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'currency' => $this->resource->currency,
                'line_count' => $lines->count(),
                'item_count' => $lines->sum('quantity'),
            ],
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}
