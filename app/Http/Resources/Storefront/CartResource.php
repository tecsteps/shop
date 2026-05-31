<?php

namespace App\Http\Resources\Storefront;

use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializes a cart for the storefront API, including its lines and computed
 * totals. All amounts are integers in minor units (cents).
 *
 * @mixin Cart
 */
class CartResource extends JsonResource
{
    /**
     * This resource owns the full response body; no `data` wrapping.
     */
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $lines = $this->lines;
        $subtotal = (int) $lines->sum('line_subtotal_amount');
        $discount = (int) $lines->sum('line_discount_amount');
        $total = (int) $lines->sum('line_total_amount');

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
                'item_count' => (int) $lines->sum('quantity'),
            ],
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
