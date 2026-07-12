<?php

namespace App\Services;

use App\Models\Checkout;
use App\Models\Discount;
use App\Models\ShippingRate;
use App\Models\TaxSettings;
use App\ValueObjects\DiscountResult;
use App\ValueObjects\PricingResult;

final class PricingEngine
{
    public function __construct(
        private readonly DiscountService $discounts,
        private readonly ShippingCalculator $shipping,
        private readonly TaxCalculator $taxes,
    ) {}

    public function calculate(Checkout $checkout): PricingResult
    {
        $checkout->loadMissing(['cart.lines.variant.product.collections', 'store']);
        $cart = $checkout->cart;
        $subtotal = (int) $cart->lines->sum(fn ($line): int => (int) $line->unit_price_amount * (int) $line->quantity);
        $discountResult = new DiscountResult(0);

        if ($checkout->discount_code !== null && $checkout->discount_code !== '') {
            $discount = $this->discounts->validate($checkout->discount_code, $checkout->store, $cart);
            $discountResult = $this->discounts->calculate($discount, $subtotal, $cart->lines);
            $this->applyAllocations($cart->lines, $discount, $discountResult);
        } else {
            foreach ($cart->lines as $line) {
                $line->update(['line_discount_amount' => 0, 'line_total_amount' => $line->line_subtotal_amount]);
            }
        }

        $shipping = 0;
        if ($checkout->shipping_method_id !== null && $this->shipping->requiresShipping($cart)) {
            $rate = ShippingRate::query()->find($checkout->shipping_method_id);
            $shipping = $rate === null ? 0 : (int) ($this->shipping->calculate($rate, $cart) ?? 0);
        }
        if ($discountResult->freeShipping) {
            $shipping = 0;
        }

        $lineAmounts = $cart->lines->map(fn ($line): int => max(0, (int) $line->line_subtotal_amount - (int) ($discountResult->allocations[$line->id] ?? 0)))->all();
        $settings = TaxSettings::withoutGlobalScopes()->where('store_id', $checkout->store_id)->first();
        $taxResult = $this->taxes->calculateLines($lineAmounts, $shipping, $settings, (array) ($checkout->shipping_address_json ?? []));
        $inclusive = (bool) ($settings?->prices_include_tax ?? false);
        $discountedSubtotal = max(0, $subtotal - $discountResult->amount);
        $total = $discountedSubtotal + $shipping + ($inclusive ? 0 : $taxResult->totalAmount);

        $result = new PricingResult(
            subtotal: $subtotal,
            discount: $discountResult->amount,
            shipping: $shipping,
            taxLines: $taxResult->taxLines,
            taxTotal: $taxResult->totalAmount,
            total: $total,
            currency: (string) $cart->currency,
        );

        $checkout->totals_json = $result->toArray();
        $checkout->save();

        return $result;
    }

    /** @param iterable<mixed> $lines */
    public function calculateSubtotal(iterable $lines): int
    {
        return (int) collect($lines)->sum(fn (mixed $line): int => is_array($line)
            ? (int) ($line['unit_price_amount'] ?? $line['price'] ?? 0) * (int) ($line['quantity'] ?? 0)
            : (int) ($line->unit_price_amount ?? $line->price_amount ?? 0) * (int) ($line->quantity ?? 0));
    }

    private function applyAllocations(iterable $lines, Discount $discount, DiscountResult $result): void
    {
        foreach ($lines as $line) {
            $allocation = (int) ($result->allocations[$line->id] ?? 0);
            $line->update([
                'line_discount_amount' => $allocation,
                'line_total_amount' => max(0, (int) $line->line_subtotal_amount - $allocation),
            ]);
        }
    }
}
