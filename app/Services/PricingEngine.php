<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Checkout;
use App\Models\ShippingRate;
use App\Models\Store;
use App\Models\TaxSettings;
use App\ValueObjects\PricingResult;
use App\ValueObjects\TaxLine;

class PricingEngine
{
    public function __construct(
        public CartService $cartService,
        public DiscountService $discountService,
        public ShippingCalculator $shippingCalculator,
        public TaxCalculator $taxCalculator,
    ) {}

    public function calculate(Checkout $checkout): PricingResult
    {
        $checkout->load('cart.lines.variant');
        /** @var Cart $cart */
        $cart = $checkout->cart;
        /** @var Store|null $store */
        $store = $cart->store()->withoutGlobalScopes()->first();

        $subtotal = 0;
        $lines = [];
        foreach ($cart->lines as $line) {
            $lineSubtotal = $line->unit_price_amount * $line->quantity;
            $subtotal += $lineSubtotal;
            $lines[] = ['id' => $line->id, 'subtotal' => $lineSubtotal];
        }

        $discountAmount = 0;
        $freeShipping = false;

        if ($checkout->discount_code && $store) {
            try {
                $discount = $this->discountService->validate($checkout->discount_code, $store, $cart);
                $discountResult = $this->discountService->calculate($discount, $subtotal, $lines);
                $discountAmount = $discountResult->totalDiscount;
                $freeShipping = $discountResult->freeShipping;
            } catch (\App\Exceptions\InvalidDiscountException) {
                // Discount is invalid, skip
            }
        }

        $discountedSubtotal = $subtotal - $discountAmount;

        $shippingAmount = 0;
        if ($checkout->shipping_method_id && ! $freeShipping) {
            $rate = ShippingRate::withoutGlobalScopes()->find($checkout->shipping_method_id);
            if ($rate) {
                $shippingAmount = $this->shippingCalculator->calculate($rate, $cart);
            }
        }

        if ($freeShipping) {
            $shippingAmount = 0;
        }

        $taxLines = [];
        $taxTotal = 0;
        $taxSettings = TaxSettings::where('store_id', $store?->id)->first();

        if ($taxSettings) {
            $address = $checkout->shipping_address_json ?? [];

            /** @var array{default_rate?: int} $config */
            $config = $taxSettings->config_json ?? [];
            $rate = $config['default_rate'] ?? 0;

            if ($rate > 0) {
                if ($taxSettings->prices_include_tax) {
                    $productTax = $this->taxCalculator->extractInclusive($discountedSubtotal, $rate);
                    $taxLines[] = new TaxLine(name: 'Tax', rate: $rate, amount: $productTax);
                    $taxTotal += $productTax;
                } else {
                    $productTax = $this->taxCalculator->addExclusive($discountedSubtotal, $rate);
                    $taxLines[] = new TaxLine(name: 'Tax', rate: $rate, amount: $productTax);
                    $taxTotal += $productTax;
                }

                if ($shippingAmount > 0) {
                    $shippingTax = $this->taxCalculator->addExclusive($shippingAmount, $rate);
                    if ($shippingTax > 0) {
                        $taxLines[] = new TaxLine(name: 'Shipping Tax', rate: $rate, amount: $shippingTax);
                        $taxTotal += $shippingTax;
                    }
                }
            }
        }

        if ($taxSettings && $taxSettings->prices_include_tax) {
            $total = $discountedSubtotal + $shippingAmount;
        } else {
            $total = $discountedSubtotal + $shippingAmount + $taxTotal;
        }

        $currency = $cart->currency ?? 'USD';

        $result = new PricingResult(
            subtotal: $subtotal,
            discount: $discountAmount,
            shipping: $shippingAmount,
            taxLines: $taxLines,
            taxTotal: $taxTotal,
            total: $total,
            currency: $currency,
        );

        $checkout->update(['totals_json' => $result->toArray()]);

        return $result;
    }
}
