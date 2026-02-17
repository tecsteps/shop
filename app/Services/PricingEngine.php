<?php

namespace App\Services;

use App\Enums\DiscountValueType;
use App\Models\Checkout;
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
        $cart = $checkout->cart()->with('lines.variant.product')->first();
        $store = $checkout->store()->first();

        // Step 1: Line subtotals
        $lineSubtotals = [];
        foreach ($cart->lines as $line) {
            $lineSubtotals[] = [
                'line_subtotal_amount' => $line->unit_price_amount * $line->quantity,
                'product_id' => $line->variant->product_id,
            ];
        }

        // Step 2: Cart subtotal
        $subtotal = array_sum(array_column($lineSubtotals, 'line_subtotal_amount'));

        // Step 3: Discount
        $discountAmount = 0;
        $discount = null;
        $isFreeShipping = false;

        if ($checkout->discount_code) {
            try {
                $discount = $this->discountService->validate($checkout->discount_code, $store, $cart);
                $result = $this->discountService->calculate($discount, $subtotal, $lineSubtotals);
                $discountAmount = $result['total_discount'];

                if ($discount->value_type === DiscountValueType::FreeShipping) {
                    $isFreeShipping = true;
                }
            } catch (\Throwable) {
                // Invalid discount - ignore and continue without discount
            }
        }

        // Step 4: Discounted subtotal
        $discountedSubtotal = $subtotal - $discountAmount;

        // Step 5: Shipping
        $shippingAmount = 0;
        if ($checkout->shipping_method_id) {
            $rate = \App\Models\ShippingRate::find($checkout->shipping_method_id);
            if ($rate) {
                $shippingAmount = $this->shippingCalculator->calculate($rate, $cart) ?? 0;
            }
        }

        if ($isFreeShipping) {
            $shippingAmount = 0;
        }

        // Step 6: Tax
        $taxLines = [];
        $taxTotal = 0;

        $taxSettings = TaxSettings::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->first();

        if ($taxSettings) {
            $taxableAmount = $discountedSubtotal;
            $config = $taxSettings->config_json ?? [];
            $taxShipping = $config['tax_shipping'] ?? false;

            if ($taxShipping) {
                $taxableAmount += $shippingAmount;
            }

            $taxResult = $this->taxCalculator->calculate($taxableAmount, $taxSettings, $checkout->shipping_address_json ?? []);
            $taxLines = $taxResult['tax_lines'];
            $taxTotal = $taxResult['total'];
        }

        // Step 7: Total
        if ($taxSettings && $taxSettings->prices_include_tax) {
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
