<?php

namespace App\Services;

use App\Models\Checkout;
use App\Models\Discount;
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
        $cart = $checkout->cart()->with('lines.variant')->first();
        $store = $checkout->store;

        // Step 1: Line subtotals
        $lines = $cart->lines;
        $subtotal = $lines->sum('line_subtotal_amount');

        // Step 2: Discount
        $discountAmount = 0;
        $isFreeShipping = false;
        $discount = null;

        if ($checkout->discount_code) {
            try {
                $discount = $this->discountService->validate($checkout->discount_code, $store, $cart);
                $result = $this->discountService->calculate($discount, $subtotal, $lines);
                $discountAmount = $result->totalDiscount;
                $isFreeShipping = $result->isFreeShipping;

                // Apply line allocations to cart lines
                foreach ($result->lineAllocations as $lineId => $allocation) {
                    $line = $lines->firstWhere('id', $lineId);

                    if ($line) {
                        $line->line_discount_amount = $allocation;
                        $line->line_total_amount = $line->line_subtotal_amount - $allocation;
                        $line->save();
                    }
                }
            } catch (\App\Exceptions\InvalidDiscountException) {
                // Discount is invalid, continue without it
            }
        }

        // Reset discounts on non-allocated lines
        foreach ($lines as $line) {
            if (! $discount || empty($result->lineAllocations[$line->id] ?? null)) {
                if ($line->line_discount_amount !== 0) {
                    $line->line_discount_amount = 0;
                    $line->line_total_amount = $line->line_subtotal_amount;
                    $line->save();
                }
            }
        }

        // Step 3: Discounted subtotal
        $discountedSubtotal = $subtotal - $discountAmount;

        // Step 4: Shipping
        $shippingAmount = 0;

        if ($checkout->shipping_method_id) {
            $shippingRate = $checkout->shippingRate;

            if ($shippingRate) {
                $calculatedAmount = $this->shippingCalculator->calculate($shippingRate, $cart);
                $shippingAmount = $calculatedAmount ?? 0;
            }
        }

        if ($isFreeShipping) {
            $shippingAmount = 0;
        }

        // Step 5: Tax
        $taxSettings = $store->taxSettings;
        $address = $checkout->shipping_address_json ?? [];

        $taxableAmount = $discountedSubtotal + $shippingAmount;
        $taxResult = $this->taxCalculator->calculate($taxableAmount, $taxSettings, $address);

        // Step 6: Total
        if ($taxSettings && $taxSettings->prices_include_tax) {
            $total = $discountedSubtotal + $shippingAmount;
        } else {
            $total = $discountedSubtotal + $shippingAmount + $taxResult->taxAmount;
        }

        return new PricingResult(
            subtotal: $subtotal,
            discount: $discountAmount,
            shipping: $shippingAmount,
            taxLines: $taxResult->taxLines,
            taxTotal: $taxResult->taxAmount,
            total: $total,
            currency: $cart->currency,
        );
    }
}
