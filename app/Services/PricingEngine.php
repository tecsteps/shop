<?php

namespace App\Services;

use App\Enums\DiscountValueType;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\Store;
use App\ValueObjects\PricingResult;

class PricingEngine
{
    public function __construct(
        private DiscountService $discountService,
        private ShippingCalculator $shippingCalculator,
        private TaxCalculator $taxCalculator,
    ) {}

    public function calculate(Cart $cart, Store $store, ?Checkout $checkout = null): PricingResult
    {
        $cart->loadMissing('lines.variant.product');

        // Step 1: Line subtotals
        $subtotal = 0;

        foreach ($cart->lines as $line) {
            $subtotal += $line->unit_price_amount * $line->quantity;
        }

        // Step 2-3: Discount
        $discountAmount = 0;
        $discount = null;
        $discountCode = $checkout?->discount_code ?? $cart->discount_code;
        $isFreeShipping = false;

        if ($discountCode) {
            try {
                $discount = $this->discountService->validate($discountCode, $cart);
                $discountAmount = $this->discountService->calculateDiscountAmount($discount, $cart);

                if ($discount->value_type === DiscountValueType::FreeShipping) {
                    $isFreeShipping = true;
                    $discountAmount = 0;
                }
            } catch (\Throwable) {
                // Invalid discount - ignore
            }
        }

        // Step 4: Discounted subtotal
        $discountedSubtotal = $subtotal - $discountAmount;

        // Step 5: Shipping
        $shippingAmount = 0;

        if ($checkout && $checkout->selected_shipping_rate_id) {
            $shippingAmount = $checkout->shipping_amount ?? 0;
        } elseif ($checkout) {
            $address = $checkout->shipping_address_json ?? [];

            if (! empty($address)) {
                $zone = $this->shippingCalculator->getMatchingZone($address, $store);

                if ($zone) {
                    $rates = $this->shippingCalculator->getAvailableRates($zone, $cart);

                    if (! empty($rates)) {
                        $shippingAmount = $rates[0]['amount'];
                    }
                }
            }
        }

        if ($isFreeShipping) {
            $shippingAmount = 0;
        }

        // Step 6: Tax - build line items with discounted amounts
        $lineItems = [];

        if ($discount && $discountAmount > 0) {
            $allocations = $this->discountService->allocateDiscount($discount, $cart);

            foreach ($cart->lines as $line) {
                $lineDiscount = $allocations[$line->id] ?? 0;
                $lineItems[] = ['amount' => ($line->unit_price_amount * $line->quantity) - $lineDiscount];
            }
        } else {
            foreach ($cart->lines as $line) {
                $lineItems[] = ['amount' => $line->unit_price_amount * $line->quantity];
            }
        }

        $address = $checkout?->shipping_address_json ?? [];
        $taxLines = $this->taxCalculator->calculate($lineItems, $shippingAmount, $address, $store);
        $taxTotal = 0;

        foreach ($taxLines as $taxLine) {
            $taxTotal += $taxLine->amount;
        }

        // Step 7: Total
        $taxSettings = $store->taxSettings;
        $pricesIncludeTax = $taxSettings?->prices_include_tax ?? false;

        if ($pricesIncludeTax) {
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
            currency: $cart->currency ?? $store->default_currency,
        );
    }
}
