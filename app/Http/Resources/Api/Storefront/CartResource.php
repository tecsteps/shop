<?php

namespace App\Http\Resources\Api\Storefront;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $lines = $this->cartLines;

        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'customer_id' => $this->customer_id,
            'currency' => $this->currency,
            'cart_version' => $this->cart_version,
            'status' => $this->status->value,
            'lines' => CartLineResource::collection($lines),
            'totals' => [
                'subtotal' => (int) $lines->sum('line_subtotal_amount'),
                'discount' => (int) $lines->sum('line_discount_amount'),
                'total' => (int) $lines->sum('line_total_amount'),
                'currency' => $this->currency,
                'line_count' => $lines->count(),
                'item_count' => (int) $lines->sum('quantity'),
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
