<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('lines.variant.product', 'lines.variant.inventoryItem');

        $lines = $this->lines;
        $subtotal = $lines->sum('line_subtotal_amount');
        $discount = $lines->sum('line_discount_amount');
        $total = $lines->sum('line_total_amount');

        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'customer_id' => $this->customer_id,
            'currency' => $this->currency,
            'cart_version' => $this->cart_version,
            'status' => $this->status->value,
            'lines' => CartLineResource::collection($lines),
            'totals' => [
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'currency' => $this->currency,
                'line_count' => $lines->count(),
                'item_count' => $lines->sum('quantity'),
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
