<?php

namespace App\Http\Resources\Storefront\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartLineResource extends JsonResource
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
            'variant_id' => $this->variant_id,
            'quantity' => $this->quantity,
            'unit_price_amount' => $this->unit_price_amount,
            'line_subtotal_amount' => $this->line_subtotal_amount,
            'line_discount_amount' => $this->line_discount_amount,
            'line_total_amount' => $this->line_total_amount,
            'product' => $this->whenLoaded('variant', fn (): ?array => $this->variant?->relationLoaded('product') ? [
                'id' => $this->variant->product?->id,
                'title' => $this->variant->product?->title,
                'handle' => $this->variant->product?->handle,
            ] : null),
            'variant' => $this->whenLoaded('variant', fn (): array => [
                'id' => $this->variant->id,
                'sku' => $this->variant->sku,
                'requires_shipping' => $this->variant->requires_shipping,
                'options' => $this->variant->relationLoaded('optionValues')
                    ? $this->variant->optionValues->map(fn ($value): array => [
                        'name' => $value->option?->name,
                        'value' => $value->value,
                    ])->values()->all()
                    : [],
            ]),
        ];
    }
}
