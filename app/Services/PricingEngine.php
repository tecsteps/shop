<?php

namespace App\Services;

use App\Enums\DiscountValueType;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\TaxSettings;
use App\ValueObjects\PricingResult;
use App\ValueObjects\TaxLine;

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
        $checkout->load('cart.lines.variant.product.collections');

        $cart = $checkout->cart;
        $lines = $cart->lines;
        $currency = $cart->currency;

        // Step 1: Line subtotals
        $subtotal = 0;
        foreach ($lines as $line) {
            $lineSubtotal = $line->unit_price_amount * $line->quantity;
            $subtotal += $lineSubtotal;
        }

        // Step 2 & 3: Discount
        $discountTotal = 0;
        $allocations = [];
        $discount = $this->resolveDiscount($checkout);

        if ($discount) {
            $result = $this->discountService->calculate($discount, $subtotal, $lines);
            $discountTotal = $result['total'];
            $allocations = $result['allocations'];

            // Update line discount amounts
            foreach ($lines as $line) {
                $lineDiscount = $allocations[$line->id] ?? 0;
                $line->line_discount_amount = $lineDiscount;
                $line->line_total_amount = $line->line_subtotal_amount - $lineDiscount;
                $line->save();
            }
        }

        // Step 4: Discounted subtotal
        $discountedSubtotal = $subtotal - $discountTotal;

        // Step 5: Shipping
        $shippingAmount = $this->calculateShipping($checkout, $discount);

        // Step 6: Tax
        $taxResult = $this->calculateTax($checkout, $discountedSubtotal, $shippingAmount);
        $taxLines = $taxResult['tax_lines'];
        $taxTotal = $taxResult['tax_total'];

        // Step 7: Total
        $total = $discountedSubtotal + $shippingAmount + $taxTotal;

        $pricingResult = new PricingResult(
            subtotal: $subtotal,
            discount: $discountTotal,
            shipping: $shippingAmount,
            taxLines: $taxLines,
            taxTotal: $taxTotal,
            total: $total,
            currency: $currency,
        );

        // Snapshot totals on the checkout
        $checkout->update([
            'totals_json' => $pricingResult->toArray(),
        ]);

        return $pricingResult;
    }

    /**
     * Resolve the discount applied to this checkout.
     */
    private function resolveDiscount(Checkout $checkout): ?Discount
    {
        if (! $checkout->discount_code) {
            return null;
        }

        return Discount::withoutGlobalScopes()
            ->where('store_id', $checkout->store_id)
            ->whereRaw('LOWER(code) = ?', [strtolower($checkout->discount_code)])
            ->first();
    }

    /**
     * Calculate shipping cost for the checkout.
     */
    private function calculateShipping(Checkout $checkout, ?Discount $discount): int
    {
        // Free shipping discount override
        if ($discount && $discount->value_type === DiscountValueType::FreeShipping) {
            return 0;
        }

        if (! $checkout->shipping_method_id) {
            return 0;
        }

        $rate = \App\Models\ShippingRate::find($checkout->shipping_method_id);

        if (! $rate) {
            return 0;
        }

        return $this->shippingCalculator->calculate($rate, $checkout->cart) ?? 0;
    }

    /**
     * Calculate tax for the checkout.
     *
     * @return array{tax_lines: array<TaxLine>, tax_total: int}
     */
    private function calculateTax(Checkout $checkout, int $discountedSubtotal, int $shippingAmount): array
    {
        $taxSettings = TaxSettings::where('store_id', $checkout->store_id)->first();

        if (! $taxSettings) {
            return ['tax_lines' => [], 'tax_total' => 0];
        }

        $address = $checkout->shipping_address_json ?? [];

        // Calculate tax on discounted subtotal
        $itemTaxResult = $this->taxCalculator->calculate(
            $discountedSubtotal,
            $taxSettings,
            $address
        );

        $taxLines = $itemTaxResult['tax_lines'];
        $taxTotal = $itemTaxResult['tax_total'];

        // Calculate tax on shipping if applicable
        $config = $taxSettings->config_json ?? [];
        $taxShipping = $config['tax_shipping'] ?? false;

        if ($taxShipping && $shippingAmount > 0) {
            $shippingTaxResult = $this->taxCalculator->calculate(
                $shippingAmount,
                $taxSettings,
                $address
            );

            foreach ($shippingTaxResult['tax_lines'] as $shippingTaxLine) {
                $taxLines[] = new TaxLine(
                    name: $shippingTaxLine->name.' (Shipping)',
                    rate: $shippingTaxLine->rate,
                    amount: $shippingTaxLine->amount,
                );
            }

            $taxTotal += $shippingTaxResult['tax_total'];
        }

        return ['tax_lines' => $taxLines, 'tax_total' => $taxTotal];
    }
}
