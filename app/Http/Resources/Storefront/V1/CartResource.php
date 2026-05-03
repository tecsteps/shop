<?php

namespace App\Http\Resources\Storefront\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $lines = $this->whenLoaded('lines');

        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'customer_id' => $this->customer_id,
            'currency' => $this->currency,
            'status' => $this->status?->value,
            'cart_version' => $this->cart_version,
            'line_count' => $this->relationLoaded('lines') ? $this->lines->sum('quantity') : null,
            'totals' => $this->relationLoaded('lines') ? [
                'subtotal' => $this->lines->sum('line_subtotal_amount'),
                'discount' => $this->lines->sum('line_discount_amount'),
                'total' => $this->lines->sum('line_total_amount'),
                'currency' => $this->currency,
            ] : null,
            'lines' => CartLineResource::collection($lines),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
