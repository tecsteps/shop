<?php

namespace App\Http\Resources\Storefront;

use App\Models\Checkout;
use App\Models\Discount;
use App\Services\ShippingCalculator;
use App\ValueObjects\Address;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Checkout payload for the storefront API (spec 02 §2.2).
 *
 * @mixin Checkout
 */
class CheckoutResource extends JsonResource
{
    /**
     * The API returns the checkout payload unwrapped (spec 02 §2.2).
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
        $this->resource->loadMissing(['cart.lines.variant.product']);

        $cart = $this->cart;
        $totals = $this->totals_json ?? [];

        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'cart_id' => $this->cart_id,
            'customer_id' => $this->customer_id,
            'status' => $this->status->value,
            'email' => $this->email,
            'shipping_address_json' => $this->shipping_address_json,
            'billing_address_json' => $this->billing_address_json,
            'shipping_method_id' => $this->shipping_method_id,
            'payment_method' => $this->payment_method?->value,
            'discount_code' => $this->discount_code,
            'lines' => $cart->lines->map(fn ($line): array => [
                'variant_id' => $line->variant_id,
                'product_title' => $line->variant?->product?->title,
                'variant_title' => $line->variant?->title(),
                'sku' => $line->variant?->sku,
                'quantity' => $line->quantity,
                'unit_price_amount' => $line->unit_price_amount,
                'line_total_amount' => $line->line_total_amount,
            ])->all(),
            'totals' => [
                'subtotal' => $totals['subtotal'] ?? 0,
                'discount' => $totals['discount'] ?? 0,
                'shipping' => $totals['shipping'] ?? 0,
                'tax' => $totals['tax'] ?? 0,
                'total' => $totals['total'] ?? 0,
                'currency' => $totals['currency'] ?? $cart->currency,
            ],
            'available_shipping_methods' => $this->availableShippingMethods(),
            'applied_discounts' => $this->appliedDiscounts($totals),
            'tax_provider_snapshot_json' => $this->tax_provider_snapshot_json,
            'expires_at' => $this->expires_at?->toIso8601ZuluString(),
            'created_at' => $this->created_at?->toIso8601ZuluString(),
        ];
    }

    /**
     * Rates available for the checkout's shipping address.
     *
     * @return array<int, array<string, mixed>>
     */
    private function availableShippingMethods(): array
    {
        if (empty($this->shipping_address_json)) {
            return [];
        }

        $methods = app(ShippingCalculator::class)->getAvailableRates(
            $this->store,
            Address::fromArray($this->shipping_address_json),
            $this->cart,
        );

        return $methods->map(fn ($rate): array => [
            'id' => $rate->id,
            'name' => $rate->name,
            'type' => $rate->type->value,
            'price_amount' => $rate->amount,
            'currency' => $this->cart->currency,
            'estimated_days_min' => $rate->estimatedDaysMin,
            'estimated_days_max' => $rate->estimatedDaysMax,
        ])->all();
    }

    /**
     * The applied code discount with its calculated amount.
     *
     * @param  array<string, mixed>  $totals
     * @return array<int, array<string, mixed>>
     */
    private function appliedDiscounts(array $totals): array
    {
        if ($this->discount_code === null) {
            return [];
        }

        $discount = Discount::query()
            ->where('store_id', $this->store_id)
            ->whereRaw('lower(code) = ?', [mb_strtolower($this->discount_code)])
            ->first();

        if ($discount === null) {
            return [];
        }

        return [[
            'code' => $discount->code,
            'type' => $discount->value_type->value,
            'value_amount' => $discount->value_amount,
            'applied_amount' => $totals['discount'] ?? 0,
            'description' => null,
        ]];
    }
}
