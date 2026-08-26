<?php

namespace App\Services;

use App\Exceptions\InvalidDiscountException;
use App\Models\Checkout;
use App\Models\ShippingRate;
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
        $cart = $checkout->cart()->with('lines.variant.product')->first();
        $store = $checkout->store;
        $currency = $cart->currency;

        $lineSubtotals = [];
        $subtotal = 0;

        foreach ($cart->lines as $line) {
            $sub = $line->unit_price_amount * $line->quantity;
            $lineSubtotals[$line->id] = $sub;
            $subtotal += $sub;
        }

        $discount = 0;
        $freeShipping = false;

        if ($checkout->discount_code) {
            try {
                $discountModel = $this->discountService->validate($checkout->discount_code, $store, $cart);
                $lineData = $cart->lines->map(fn ($line) => [
                    'id' => $line->id,
                    'subtotal' => $lineSubtotals[$line->id],
                    'product_id' => $line->variant?->product_id,
                ])->all();

                $result = $this->discountService->calculate($discountModel, $subtotal, $lineData);
                $discount = $result->amount;
                $freeShipping = $result->freeShipping;
            } catch (InvalidDiscountException) {
                $discount = 0;
            }
        }

        $discountedSubtotal = $subtotal - $discount;

        $shipping = 0;

        if (! $freeShipping && $this->requiresShipping($cart->lines)) {
            if ($checkout->shipping_method_id) {
                $rate = ShippingRate::find($checkout->shipping_method_id);

                if ($rate) {
                    $shipping = $this->shippingCalculator->calculate($rate, $cart);
                }
            }
        }

        $taxLines = [];
        $taxTotal = 0;
        $taxSettings = TaxSettings::where('store_id', $store->id)->first();

        if ($taxSettings) {
            $rate = $this->resolveTaxRate($taxSettings);

            if ($rate > 0) {
                if ($taxSettings->prices_include_tax) {
                    $taxTotal = $this->taxCalculator->extractInclusive($discountedSubtotal + $shipping, $rate);
                } else {
                    $taxTotal = $this->taxCalculator->addExclusive($discountedSubtotal + $shipping, $rate);
                }

                $taxLines[] = new TaxLine('Tax', $rate, $taxTotal);
            }
        }

        $total = $discountedSubtotal + $shipping + $taxTotal;

        return new PricingResult($subtotal, $discount, $shipping, $taxLines, $taxTotal, $total, $currency);
    }

    private function resolveTaxRate(TaxSettings $settings): int
    {
        $config = $settings->config_json ?? [];

        if (isset($config['default_tax_rate'])) {
            return (int) $config['default_tax_rate'];
        }

        if (isset($config['rate'])) {
            return (int) $config['rate'];
        }

        foreach ($config['tax_rates'] ?? [] as $rate) {
            if (isset($rate['rate'])) {
                return (int) $rate['rate'];
            }
        }

        return 0;
    }

    private function requiresShipping(iterable $lines): bool
    {
        foreach ($lines as $line) {
            if ($line->variant?->requires_shipping) {
                return true;
            }
        }

        return false;
    }
}
