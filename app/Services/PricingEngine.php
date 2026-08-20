<?php

namespace App\Services;

use App\Models\Checkout;
use App\Models\Discount;
use App\Models\TaxSettings;
use App\ValueObjects\PricingResult;

class PricingEngine
{
    public function __construct(private readonly DiscountService $discounts, private readonly ShippingCalculator $shipping, private readonly TaxCalculator $taxes) {}

    public function calculate(Checkout $checkout): PricingResult
    {
        $checkout->load(['cart.lines.variant.product.collections', 'shippingRate']);
        $cart = $checkout->cart;
        $lines = $cart->lines;
        $subtotal = (int) $lines->sum(fn ($line): int => $line->unit_price_amount * $line->quantity);
        $discountAmount = 0;
        $freeShipping = false;

        foreach ($lines as $line) {
            $line->updateQuietly(['line_discount_amount' => 0, 'line_total_amount' => $line->line_subtotal_amount]);
        }

        if ($checkout->discount_code !== null) {
            $discount = Discount::withoutGlobalScopes()->where('store_id', $checkout->store_id)->whereRaw('lower(code) = ?', [strtolower($checkout->discount_code)])->first();

            if ($discount?->isAvailable()) {
                $result = $this->discounts->calculate($discount, $subtotal, $lines->map(fn ($line): array => [
                    'line_id' => $line->id,
                    'amount' => $line->unit_price_amount * $line->quantity,
                    'product_id' => $line->variant->product_id,
                    'collection_ids' => $line->variant->product->collections->modelKeys(),
                ])->all());
                $discountAmount = $result->amount;
                $freeShipping = $result->freeShipping;

                foreach ($lines as $line) {
                    $lineDiscount = $result->allocations[$line->id] ?? 0;
                    $line->updateQuietly(['line_discount_amount' => $lineDiscount, 'line_total_amount' => max(0, $line->line_subtotal_amount - $lineDiscount)]);
                }
            }
        }

        $shippingAmount = $checkout->shippingRate !== null && ! $freeShipping ? $this->shipping->calculate($checkout->shippingRate, $cart) : 0;
        $taxSettings = TaxSettings::withoutGlobalScopes()->firstOrCreate(['store_id' => $checkout->store_id], ['default_rate_basis_points' => 0]);
        $taxLines = [];

        foreach ($lines as $line) {
            $lineAmount = max(0, $line->line_subtotal_amount - $line->line_discount_amount);
            $taxLines = [...$taxLines, ...$this->taxes->calculate($lineAmount, $taxSettings, $checkout->shipping_address_json ?? [])->lines];
        }

        if ($shippingAmount > 0) {
            $taxLines = [...$taxLines, ...$this->taxes->calculate($shippingAmount, $taxSettings, $checkout->shipping_address_json ?? [])->lines];
        }

        $taxTotal = (int) collect($taxLines)->sum('amount');
        $discountedSubtotal = max(0, $subtotal - $discountAmount);
        $pricesIncludeTax = (bool) $taxSettings->prices_include_tax || $taxSettings->mode === 'inclusive';
        $total = max(0, $discountedSubtotal + $shippingAmount + ($pricesIncludeTax ? 0 : $taxTotal));
        $result = new PricingResult($subtotal, $discountAmount, $shippingAmount, $taxLines, $taxTotal, $total, $cart->currency);
        $checkout->update(['totals_json' => $result->toArray()]);

        return $result;
    }
}
