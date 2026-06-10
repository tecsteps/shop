<?php

namespace App\Http\Resources\Storefront;

use App\Models\Cart;
use App\Models\CartLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Cart
 */
class CartResource extends JsonResource
{
    /**
     * Storefront cart responses are not wrapped in a "data" key (spec 02
     * section 2.1).
     *
     * @var string|null
     */
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $lines = $this->lines()
            ->with(['variant.product.media', 'variant.optionValues', 'variant.inventoryItem'])
            ->get();

        return [
            'id' => $this->getKey(),
            'store_id' => $this->store_id,
            'customer_id' => $this->customer_id,
            'currency' => $this->currency,
            'cart_version' => $this->cart_version,
            'status' => $this->status->value,
            'lines' => $lines->map(fn (CartLine $line): array => $this->lineToArray($line))->all(),
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

    /**
     * @return array<string, mixed>
     */
    protected function lineToArray(CartLine $line): array
    {
        $variant = $line->variant;
        $image = $variant?->product?->media->first();

        return [
            'id' => $line->getKey(),
            'variant_id' => $line->variant_id,
            'product_title' => $variant?->product?->title,
            'variant_title' => $variant?->optionValues->pluck('value')->implode(' / ') ?: null,
            'sku' => $variant?->sku,
            'quantity' => $line->quantity,
            'unit_price_amount' => $line->unit_price_amount,
            'line_subtotal_amount' => $line->line_subtotal_amount,
            'line_discount_amount' => $line->line_discount_amount,
            'line_total_amount' => $line->line_total_amount,
            'image_url' => $image !== null ? Storage::disk('public')->url($image->storage_key) : null,
            'requires_shipping' => (bool) ($variant?->requires_shipping ?? false),
            'available_quantity' => $variant?->inventoryItem?->availableQuantity(),
        ];
    }
}
