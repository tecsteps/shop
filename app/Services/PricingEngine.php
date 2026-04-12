<?php

namespace App\Services;

use App\Models\Checkout;
use App\Models\ShippingRate;
use App\Models\Store;
use App\Models\TaxSettings;
use App\ValueObjects\PricingResult;
use App\ValueObjects\TaxLine;

class PricingEngine
{
    public function __construct(
        private readonly DiscountService $discountService,
        private readonly ShippingCalculator $shippingCalculator,
        private readonly TaxCalculator $taxCalculator,
    ) {}

    public function calculate(Checkout $checkout): PricingResult
    {
        $cart = $checkout->cart()->with('lines.variant')->first();

        if ($cart === null) {
            return new PricingResult(0, 0, 0, [], 0, 0, 'USD');
        }

        $store = Store::withoutGlobalScopes()->find($checkout->store_id);

        $subtotal = (int) $cart->lines->sum('line_subtotal_amount');

        $discount = 0;
        $freeShipping = false;

        if ($checkout->discount_code !== null && $checkout->discount_code !== '' && $store !== null) {
            $discountModel = $this->discountService->validate($checkout->discount_code, $store, $cart);
            $result = $this->discountService->calculate($discountModel, $subtotal, $cart->lines);
            $discount = $result->amount;
            $freeShipping = $result->freeShipping;
        }

        $discountedSubtotal = $subtotal - $discount;

        $shipping = 0;
        if ($checkout->shipping_method_id !== null) {
            $rate = ShippingRate::query()->find($checkout->shipping_method_id);
            if ($rate !== null) {
                $shipping = $this->shippingCalculator->calculate($rate, $cart);
            }
        }

        if ($freeShipping) {
            $shipping = 0;
        }

        $taxLines = [];
        $taxTotal = 0;
        $currency = (string) ($cart->currency ?? 'USD');

        $taxSettings = $store !== null
            ? TaxSettings::query()->where('store_id', $store->id)->first()
            : null;

        if ($taxSettings !== null) {
            if ((bool) $taxSettings->prices_include_tax) {
                $rateBasisPoints = (int) ($taxSettings->config_json['rate_basis_points'] ?? 0);
                $taxName = (string) ($taxSettings->config_json['name'] ?? 'Tax');
                $extractedBase = $discountedSubtotal + $shipping;
                $extracted = $this->taxCalculator->extractInclusive($extractedBase, $rateBasisPoints);
                $taxTotal = $extracted;
                $taxLines = $extracted > 0
                    ? [new TaxLine($taxName, $rateBasisPoints, $extracted)]
                    : [];
                $total = $discountedSubtotal + $shipping;
            } else {
                $taxBase = $discountedSubtotal + $shipping;
                $result = $this->taxCalculator->calculate(
                    $taxBase,
                    $taxSettings,
                    $checkout->shipping_address_json ?? []
                );
                $taxTotal = (int) $result['tax_total'];
                $taxLines = $result['tax_lines'];
                $total = $discountedSubtotal + $shipping + $taxTotal;
            }
        } else {
            $total = $discountedSubtotal + $shipping;
        }

        return new PricingResult(
            subtotal: $subtotal,
            discount: $discount,
            shipping: $shipping,
            taxLines: $taxLines,
            taxTotal: $taxTotal,
            total: $total,
            currency: $currency,
            freeShippingApplied: $freeShipping,
        );
    }
}
