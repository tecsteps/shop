<?php

namespace App\Services;

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

    /**
     * Calculate full pricing for a checkout.
     */
    public function calculate(Checkout $checkout): PricingResult
    {
        $checkout->loadMissing('cart.lines.variant', 'shippingRate');

        $cart = $checkout->cart;
        $lines = $cart->lines;

        // Step 1: Line subtotals
        $subtotal = $lines->sum(fn ($line) => $line->unit_price_amount * $line->quantity);

        // Step 2: Discount
        $discountAmount = 0;
        $freeShipping = false;

        if ($checkout->discount_code) {
            $discount = Discount::withoutGlobalScopes()
                ->where('store_id', $checkout->store_id)
                ->whereRaw('LOWER(code) = ?', [strtolower($checkout->discount_code)])
                ->where('status', 'active')
                ->where(function ($q) {
                    $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                })
                ->where(function ($q) {
                    $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                })
                ->first();

            if ($discount) {
                $lineData = $lines->map(fn ($line) => [
                    'line_id' => $line->id,
                    'subtotal' => $line->unit_price_amount * $line->quantity,
                ])->values()->all();

                $discountResult = $this->discountService->calculate($discount, $subtotal, $lineData);
                $discountAmount = $discountResult['amount'];
                $freeShipping = $discountResult['free_shipping'];
            }
        }

        // Step 3: Discounted subtotal
        $discountedSubtotal = max(0, $subtotal - $discountAmount);

        // Step 4: Shipping
        $shippingAmount = 0;
        if ($checkout->shippingRate) {
            $shippingAmount = $this->shippingCalculator->calculate($checkout->shippingRate, $cart);
        }

        if ($freeShipping) {
            $shippingAmount = 0;
        }

        // Step 5: Tax
        $taxSettings = TaxSettings::where('store_id', $checkout->store_id)->first();

        $taxableAmount = $discountedSubtotal;
        if ($taxSettings && $taxSettings->tax_shipping) {
            $taxableAmount += $shippingAmount;
        }

        $taxResult = $this->taxCalculator->calculate($taxableAmount, $taxSettings);
        $taxTotal = $taxResult['tax_total'];
        $taxLines = $taxResult['tax_lines'];

        // Step 6: Total
        if ($taxSettings && $taxSettings->prices_include_tax) {
            // Tax is already included in the subtotal
            $total = $discountedSubtotal + $shippingAmount;
        } else {
            $total = $discountedSubtotal + $shippingAmount + $taxTotal;
        }

        return new PricingResult(
            subtotal: $subtotal,
            discount: $discountAmount,
            shipping: $shippingAmount,
            taxLines: $taxLines,
            taxTotal: $taxTotal,
            total: $total,
            currency: $cart->currency,
        );
    }
}
