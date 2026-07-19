<?php

namespace App\Http\Resources\Storefront;

use App\Models\Cart;
use App\Models\CartLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Cart payload for the storefront API (spec 02 §2.1).
 *
 * @mixin Cart
 */
class CartResource extends JsonResource
{
    /**
     * The API returns the cart payload unwrapped (spec 02 §2.1).
     *
     * @var string|null
     */
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['lines.variant.product.media', 'lines.variant.inventoryItem']);

        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'customer_id' => $this->customer_id,
            'currency' => $this->currency,
            'cart_version' => $this->cart_version,
            'status' => $this->status->value,
            'lines' => $this->lines->map(fn (CartLine $line): array => [
                'id' => $line->id,
                'variant_id' => $line->variant_id,
                'product_title' => $line->variant?->product?->title,
                'variant_title' => $line->variant?->title(),
                'sku' => $line->variant?->sku,
                'quantity' => $line->quantity,
                'unit_price_amount' => $line->unit_price_amount,
                'line_subtotal_amount' => $line->line_subtotal_amount,
                'line_discount_amount' => $line->line_discount_amount,
                'line_total_amount' => $line->line_total_amount,
                'image_url' => $line->variant?->product?->media->first()?->url(),
                'requires_shipping' => (bool) ($line->variant?->requires_shipping ?? false),
                'available_quantity' => $line->variant?->availableQuantity() ?? 0,
            ])->all(),
            'totals' => [
                'subtotal' => $this->subtotal(),
                'discount' => (int) $this->lines->sum('line_discount_amount'),
                'total' => (int) $this->lines->sum('line_total_amount'),
                'currency' => $this->currency,
                'line_count' => $this->lineCount(),
                'item_count' => $this->itemCount(),
            ],
            'created_at' => $this->created_at?->toIso8601ZuluString(),
            'updated_at' => $this->updated_at?->toIso8601ZuluString(),
        ];
    }
}
