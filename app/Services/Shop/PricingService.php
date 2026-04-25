<?php

namespace App\Services\Shop;

use App\Enums\DiscountType;
use App\Models\Cart;
use App\Models\Discount;
use App\Models\ShippingRate;
use App\Models\TaxSettings;
use Illuminate\Validation\ValidationException;

class PricingService
{
    /**
     * @return array{subtotal:int,discount:int,shipping:int,tax:int,total:int,currency:string,discount_code:?string}
     */
    public function cartTotals(Cart $cart, ?ShippingRate $shippingRate = null): array
    {
        $cart->loadMissing('lines.variant.product');

        $subtotal = $cart->lines->sum(fn ($line): int => $line->quantity * $line->unit_price_amount);
        $shipping = $shippingRate?->price_amount ?? 0;
        $discount = 0;
        $freeShipping = false;

        if ($cart->discount_code) {
            $discountModel = $this->validDiscount($cart, $subtotal);

            if ($discountModel->type === DiscountType::Percentage) {
                $discount = Money::bps($subtotal, $discountModel->value_bps);
            } elseif ($discountModel->type === DiscountType::FixedAmount) {
                $discount = min($subtotal, $discountModel->value_amount);
            } elseif ($discountModel->type === DiscountType::FreeShipping) {
                $freeShipping = true;
            }
        }

        if ($freeShipping) {
            $shipping = 0;
        }

        $taxSettings = TaxSettings::query()->whereKey($cart->store_id)->first();
        $taxable = max(0, $subtotal - $discount) + $shipping;
        $tax = $taxSettings?->prices_include_tax
            ? (int) round($taxable - ($taxable / (1 + (($taxSettings->default_rate_bps ?? 1900) / 10000))))
            : Money::bps($taxable, $taxSettings->default_rate_bps ?? 1900);

        $total = $taxSettings?->prices_include_tax
            ? $taxable
            : $taxable + $tax;

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping' => $shipping,
            'tax' => $tax,
            'total' => $total,
            'currency' => $cart->currency,
            'discount_code' => $cart->discount_code,
        ];
    }

    public function validDiscount(Cart $cart, int $subtotal): Discount
    {
        $discount = Discount::query()
            ->where('code', strtoupper((string) $cart->discount_code))
            ->first();

        if (! $discount || ! $discount->is_active) {
            throw ValidationException::withMessages(['discount_code' => 'Discount code is invalid.']);
        }

        if ($discount->starts_at && $discount->starts_at->isFuture()) {
            throw ValidationException::withMessages(['discount_code' => 'Discount code is not active yet.']);
        }

        if ($discount->ends_at && $discount->ends_at->isPast()) {
            throw ValidationException::withMessages(['discount_code' => 'Discount code has expired.']);
        }

        if ($discount->usage_limit !== null && $discount->used_count >= $discount->usage_limit) {
            throw ValidationException::withMessages(['discount_code' => 'Discount code usage limit has been reached.']);
        }

        if ($discount->min_purchase_amount !== null && $subtotal < $discount->min_purchase_amount) {
            throw ValidationException::withMessages(['discount_code' => 'Discount code requires a higher cart subtotal.']);
        }

        return $discount;
    }
}

