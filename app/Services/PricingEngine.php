<?php

namespace App\Services;

use App\Enums\DiscountStatus;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\TaxSettings;
use App\ValueObjects\PricingResult;

class PricingEngine
{
    public function __construct(
        private DiscountService $discountService,
        private ShippingCalculator $shippingCalculator,
        private TaxCalculator $taxCalculator,
    ) {}

    public function calculate(Checkout $checkout): PricingResult
    {
        $cart = $checkout->cart()->with(['lines.variant'])->first();
        $store = $checkout->store;

        // Step 1 & 2: Line subtotals and cart subtotal
        $subtotal = 0;
        $lines = [];
        foreach ($cart->lines as $line) {
            $lineSubtotal = $line->unit_price_amount * $line->quantity;
            $subtotal += $lineSubtotal;
            $lines[] = ['id' => $line->id, 'subtotal' => $lineSubtotal];
        }

        // Step 3: Discount
        $discountAmount = 0;
        $isFreeShipping = false;

        if ($checkout->discount_code) {
            $discount = Discount::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->whereRaw('LOWER(code) = ?', [strtolower($checkout->discount_code)])
                ->first();

            if ($discount && $this->isDiscountValid($discount)) {
                $result = $this->discountService->calculate($discount, $subtotal, $lines);
                $discountAmount = $result->amount;
                $isFreeShipping = $result->isFreeShipping;
            }
        }

        // Step 4: Discounted subtotal
        $discountedSubtotal = $subtotal - $discountAmount;

        // Step 5: Shipping
        $shippingAmount = 0;
        if ($checkout->shipping_method_id) {
            $rate = \App\Models\ShippingRate::find($checkout->shipping_method_id);
            if ($rate) {
                $shippingAmount = $this->shippingCalculator->calculate($rate, $cart);
            }
        }

        if ($isFreeShipping) {
            $shippingAmount = 0;
        }

        // Step 6: Tax
        $taxSettings = TaxSettings::where('store_id', $store->id)->first();
        $taxableAmount = $discountedSubtotal + ($taxSettings && ($taxSettings->config_json['shipping_taxable'] ?? false) ? $shippingAmount : 0);

        $taxResult = $this->taxCalculator->calculate($taxableAmount, $taxSettings, $checkout->shipping_address_json ?? []);

        // Step 7: Total
        $taxTotal = $taxResult['tax_total'];

        if ($taxSettings && $taxSettings->prices_include_tax) {
            // Tax-inclusive: tax is already included in the subtotal, total = discounted_subtotal + shipping
            $total = $discountedSubtotal + $shippingAmount;
        } else {
            // Tax-exclusive: add tax on top
            $total = $discountedSubtotal + $shippingAmount + $taxTotal;
        }

        return new PricingResult(
            subtotal: $subtotal,
            discount: $discountAmount,
            shipping: $shippingAmount,
            taxLines: $taxResult['tax_lines'],
            taxTotal: $taxTotal,
            total: $total,
            currency: $cart->currency,
        );
    }

    private function isDiscountValid(Discount $discount): bool
    {
        if ($discount->status !== DiscountStatus::Active) {
            return false;
        }

        if ($discount->starts_at && $discount->starts_at->isFuture()) {
            return false;
        }

        if ($discount->ends_at && $discount->ends_at->isPast()) {
            return false;
        }

        if ($discount->usage_limit !== null && $discount->usage_count >= $discount->usage_limit) {
            return false;
        }

        return true;
    }
}
