<?php

namespace App\Services;

use App\Models\Checkout;
use App\Models\Discount;
use App\Models\TaxSettings;
use App\ValueObjects\DiscountResult;
use App\ValueObjects\PricingResult;

class PricingEngine
{
    public function __construct(
        private readonly DiscountService $discountService,
        private readonly ShippingCalculator $shippingCalculator,
        private readonly TaxCalculator $taxCalculator,
    ) {}

    public function calculate(Checkout $checkout): PricingResult
    {
        $checkout->loadMissing(['store', 'cart.lines.variant.product.collections', 'shippingMethod']);
        $subtotal = (int) $checkout->cart->lines->sum('line_subtotal_amount');
        $discountResult = new DiscountResult(0, []);

        if ($checkout->discount_code) {
            $discount = $this->discountService->validate($checkout->discount_code, $checkout->store, $checkout->cart);
            $discountResult = $this->discountService->calculate($discount, $subtotal, $checkout->cart->lines);
            $this->applyAllocations($checkout, $discount, $discountResult);
        }

        $discountedSubtotal = max(0, $subtotal - $discountResult->amount);
        $shipping = $checkout->shippingMethod ? ($this->shippingCalculator->calculate($checkout->shippingMethod, $checkout->cart) ?? 0) : 0;

        if ($discountResult->freeShipping) {
            $shipping = 0;
        }

        $settings = TaxSettings::query()->find($checkout->store_id) ?? new TaxSettings(['store_id' => $checkout->store_id]);
        $taxableAmount = $discountedSubtotal + $shipping;
        $tax = $this->taxCalculator->calculate($taxableAmount, $settings, $checkout->shipping_address_json ?? []);
        $total = $settings->prices_include_tax ? $taxableAmount : $taxableAmount + $tax->taxAmount;

        $result = new PricingResult($subtotal, $discountResult->amount, $shipping, $tax->lines, $tax->taxAmount, $total, $checkout->cart->currency);
        $checkout->update(['totals_json' => $result->jsonSerialize()]);

        return $result;
    }

    private function applyAllocations(Checkout $checkout, Discount $discount, DiscountResult $result): void
    {
        foreach ($checkout->cart->lines as $line) {
            $amount = $result->allocations[$line->id] ?? 0;
            $line->update(['line_discount_amount' => $amount, 'line_total_amount' => $line->line_subtotal_amount - $amount]);
        }
    }
}
