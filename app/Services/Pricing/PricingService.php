<?php

namespace App\Services\Pricing;

use App\Enums\DiscountValueType;
use App\Models\Cart;
use App\Models\Discount;
use App\Models\ShippingRate;
use App\Models\Store;
use App\Models\TaxSettings;

class PricingService
{
    public function computeTotals(
        Cart $cart,
        ?ShippingRate $shippingRate = null,
        ?Discount $discount = null,
    ): CartTotals {
        $cart->load('lines');
        $subtotal = (int) $cart->lines->sum('line_subtotal_amount');

        $discountAmount = 0;
        $freeShipping = false;

        if ($discount && $discount->isActive()) {
            [$discountAmount, $freeShipping] = $this->applyDiscount($discount, $subtotal);
        }

        $shippingAmount = $shippingRate ? $shippingRate->baseAmount() : 0;

        if ($freeShipping) {
            $shippingAmount = 0;
        }

        $store = $this->resolveStore($cart);
        $taxSettings = $store?->id ? TaxSettings::find($store->id) : null;

        $taxableAmount = max(0, $subtotal - $discountAmount);
        $taxAmount = $taxSettings
            ? (int) round($taxableAmount * $taxSettings->defaultRate())
            : 0;

        $total = $subtotal - $discountAmount + $shippingAmount + $taxAmount;

        return new CartTotals(
            subtotal: $subtotal,
            discount: $discountAmount,
            shipping: $shippingAmount,
            tax: $taxAmount,
            total: max(0, $total),
            currency: $cart->currency,
        );
    }

    private function applyDiscount(Discount $discount, int $subtotal): array
    {
        return match ($discount->value_type) {
            DiscountValueType::Percent => [
                (int) round($subtotal * ($discount->value_amount / 100)),
                false,
            ],
            DiscountValueType::Fixed => [
                min($subtotal, $discount->value_amount),
                false,
            ],
            DiscountValueType::FreeShipping => [0, true],
        };
    }

    private function resolveStore(Cart $cart): ?Store
    {
        if (app()->bound('current_store')) {
            return app('current_store');
        }

        return Store::find($cart->store_id);
    }
}
