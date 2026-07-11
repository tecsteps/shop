<?php

namespace App\Http\Resources;

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
        return [
            'id' => $this->id,
            'version' => $this->cart_version,
            'status' => $this->status,
            'currency' => $this->currency,
            'lines' => $this->whenLoaded('lines', fn () => $this->lines->map(fn ($line) => [
                'id' => $line->id,
                'variant_id' => $line->variant_id,
                'quantity' => $line->quantity,
                'unit_price_amount' => $line->unit_price_amount,
                'subtotal_amount' => $line->line_subtotal_amount,
                'discount_amount' => $line->line_discount_amount,
                'total_amount' => $line->line_total_amount,
            ])),
            'totals' => [
                'subtotal' => $this->whenLoaded('lines', fn () => $this->lines->sum('line_subtotal_amount')),
                'discount' => $this->whenLoaded('lines', fn () => $this->lines->sum('line_discount_amount')),
                'total' => $this->whenLoaded('lines', fn () => $this->lines->sum('line_total_amount')),
            ],
        ];
    }
}
