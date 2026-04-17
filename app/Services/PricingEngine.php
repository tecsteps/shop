<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\ShippingRate;
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
     * Calculate the full pricing breakdown for a checkout.
     */
    public function calculate(Checkout $checkout): PricingResult
    {
        $cart = $checkout->cart()->with('lines.variant')->first();
        $store = $cart->store()->withoutGlobalScopes()->first();

        $subtotal = $this->calculateSubtotal($cart);

        $discountAmount = 0;
        $freeShipping = false;

        if ($checkout->discount_code) {
            $discount = Discount::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->whereRaw('LOWER(code) = ?', [strtolower($checkout->discount_code)])
                ->first();

            if ($discount) {
                $result = $this->discountService->calculate($discount, $subtotal);
                $discountAmount = $result['amount'];
                $freeShipping = $result['free_shipping'];
            }
        }

        $discountedSubtotal = max(0, $subtotal - $discountAmount);

        $shippingAmount = 0;
        if (! $freeShipping && $checkout->shipping_method_id) {
            $shippingRate = ShippingRate::find($checkout->shipping_method_id);
            if ($shippingRate) {
                $shippingAmount = $this->shippingCalculator->calculate($shippingRate, $cart) ?? 0;
            }
        }

        $taxSettings = TaxSettings::where('store_id', $store->id)->first();
        $address = $checkout->shipping_address_json ?? [];

        $taxableAmount = $discountedSubtotal + $shippingAmount;
        $taxResult = $this->taxCalculator->calculate($taxableAmount, $taxSettings, $address);
        $taxTotal = $taxResult['tax_total'];

        if ($taxSettings && $taxSettings->prices_include_tax) {
            $total = $discountedSubtotal + $shippingAmount;
        } else {
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

    /**
     * Calculate subtotal from cart lines.
     */
    public function calculateSubtotal(Cart $cart): int
    {
        $cart->load('lines');

        return $cart->lines->sum('line_subtotal_amount');
    }

    /**
     * Perform a standalone pricing calculation without a checkout.
     *
     * @param  array{country?: string, province_code?: string}  $address
     */
    public function calculateForCart(
        Cart $cart,
        ?Discount $discount = null,
        ?ShippingRate $shippingRate = null,
        ?TaxSettings $taxSettings = null,
        array $address = [],
    ): PricingResult {
        $cart->load('lines.variant');

        $subtotal = $cart->lines->sum('line_subtotal_amount');

        $discountAmount = 0;
        $freeShipping = false;

        if ($discount) {
            $result = $this->discountService->calculate($discount, $subtotal);
            $discountAmount = $result['amount'];
            $freeShipping = $result['free_shipping'];
        }

        $discountedSubtotal = max(0, $subtotal - $discountAmount);

        $shippingAmount = 0;
        if (! $freeShipping && $shippingRate) {
            $shippingAmount = $this->shippingCalculator->calculate($shippingRate, $cart) ?? 0;
        }

        $taxableAmount = $discountedSubtotal + $shippingAmount;
        $taxResult = $this->taxCalculator->calculate($taxableAmount, $taxSettings, $address);
        $taxTotal = $taxResult['tax_total'];

        if ($taxSettings && $taxSettings->prices_include_tax) {
            $total = $discountedSubtotal + $shippingAmount;
        } else {
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
}
