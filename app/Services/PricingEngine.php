<?php

namespace App\Services;

use App\Models\Checkout;
use App\Models\TaxSettings;
use App\ValueObjects\PricingResult;

class PricingEngine
{
    public function __construct(
        protected DiscountService $discountService,
        protected ShippingCalculator $shippingCalculator,
        protected TaxCalculator $taxCalculator,
    ) {}

    public function calculate(Checkout $checkout): PricingResult
    {
        $cart = $checkout->cart()->with('lines.variant.product.collections')->first();
        $store = $checkout->store;

        // Step 1: Line subtotals
        $lineData = [];
        foreach ($cart->lines as $line) {
            $lineData[] = [
                'line_id' => $line->id,
                'product_id' => $line->variant->product_id,
                'collection_ids' => $line->variant->product->collections->pluck('id')->toArray(),
                'line_subtotal_amount' => $line->unit_price_amount * $line->quantity,
                'quantity' => $line->quantity,
                'unit_price_amount' => $line->unit_price_amount,
            ];
        }

        // Step 2: Cart subtotal
        $subtotal = array_sum(array_column($lineData, 'line_subtotal_amount'));

        // Step 3: Discount
        $discountAmount = 0;
        $freeShipping = false;

        if ($checkout->discount_code) {
            try {
                $discount = $this->discountService->validate($checkout->discount_code, $store, $cart);
                $result = $this->discountService->calculate($discount, $subtotal, $lineData);
                $discountAmount = $result['total_discount'];
                $freeShipping = $result['free_shipping'] ?? false;

                // Update line discount allocations on the cart
                foreach ($result['line_allocations'] as $lineId => $amount) {
                    $cart->lines()->where('id', $lineId)->update([
                        'line_discount_amount' => $amount,
                        'line_total_amount' => \Illuminate\Support\Facades\DB::raw("line_subtotal_amount - {$amount}"),
                    ]);
                }
            } catch (\App\Exceptions\InvalidDiscountException) {
                // Discount is invalid, proceed without it
            }
        }

        // Step 4: Discounted subtotal
        $discountedSubtotal = $subtotal - $discountAmount;

        // Step 5: Shipping
        $shippingAmount = 0;

        if ($checkout->shipping_method_id && ! $freeShipping) {
            $rate = \App\Models\ShippingRate::find($checkout->shipping_method_id);
            if ($rate) {
                $shippingAmount = $this->shippingCalculator->calculate($rate, $cart) ?? 0;
            }
        }

        if ($freeShipping) {
            $shippingAmount = 0;
        }

        // Step 6: Tax
        $taxSettings = TaxSettings::find($store->id);
        $taxLines = [];
        $taxTotal = 0;

        if ($taxSettings) {
            $address = $checkout->shipping_address_json ?? [];
            $taxableAmount = $discountedSubtotal + $shippingAmount;
            $taxResult = $this->taxCalculator->calculate($taxableAmount, $taxSettings, $address);
            $taxLines = $taxResult['tax_lines'];
            $taxTotal = $taxResult['tax_total'];
        }

        // Step 7: Total
        $total = $discountedSubtotal + $shippingAmount + $taxTotal;

        $pricingResult = new PricingResult(
            subtotal: $subtotal,
            discount: $discountAmount,
            shipping: $shippingAmount,
            taxLines: $taxLines,
            taxTotal: $taxTotal,
            total: $total,
            currency: $cart->currency,
        );

        // Snapshot totals on checkout
        $checkout->update([
            'totals_json' => $pricingResult->toArray(),
        ]);

        return $pricingResult;
    }
}
