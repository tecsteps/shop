<?php

namespace App\Http\Resources\Storefront;

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
        $this->resource->loadMissing('lines.variant.product.media', 'lines.variant.optionValues.option', 'lines.variant.inventoryItem');

        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'customer_id' => $this->customer_id,
            'currency' => $this->currency,
            'discount_code' => $this->discount_code,
            'cart_version' => $this->cart_version,
            'status' => $this->status->value,
            'lines' => CartLineResource::collection($this->lines),
            'totals' => [
                'subtotal' => $this->subtotalAmount(),
                'discount' => $this->discountAmount(),
                'total' => $this->totalAmount(),
                'currency' => $this->currency,
                'line_count' => $this->lines->count(),
                'item_count' => $this->itemCount(),
            ],
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
