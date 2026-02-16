<?php

namespace App\Services;

use App\Models\Checkout;
use App\Models\TaxSettings;
use App\ValueObjects\PricingResult;

class PricingEngine
{
    public function __construct(
        private TaxCalculator $taxCalculator,
    ) {}

    public function calculate(Checkout $checkout): PricingResult
    {
        $cart = $checkout->cart()->with('lines.variant')->first();
        $subtotal = $cart->lines->sum(fn ($line) => $line->quantity * $line->unit_price);

        $discountAmount = $checkout->discount_amount;
        $shippingAmount = $checkout->shipping_amount;

        $taxableAmount = $subtotal - $discountAmount;
        $taxSettings = TaxSettings::find($checkout->store_id);

        $taxLines = [];
        $taxTotal = 0;

        if ($taxSettings && $taxSettings->rate_basis_points > 0) {
            $address = $checkout->shipping_address_json ?? [];
            $taxResult = $this->taxCalculator->calculate($taxableAmount, $taxSettings, $address);
            $taxLines = $taxResult['taxLines'];
            $taxTotal = $taxResult['taxTotal'];

            if ($taxSettings->charge_tax_on_shipping && $shippingAmount > 0) {
                $shippingTax = $this->taxCalculator->calculate($shippingAmount, $taxSettings, $address);
                $taxTotal += $shippingTax['taxTotal'];
                $taxLines = array_merge($taxLines, $shippingTax['taxLines']);
            }
        }

        $total = $subtotal - $discountAmount + $shippingAmount + $taxTotal;

        return new PricingResult(
            subtotal: $subtotal,
            discount: $discountAmount,
            shipping: $shippingAmount,
            taxLines: $taxLines,
            taxTotal: $taxTotal,
            total: max(0, $total),
            currency: $cart->currency,
        );
    }
}
