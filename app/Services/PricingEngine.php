<?php

namespace App\Services;

use App\Enums\DiscountType;
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
        $discountAllocations = [];

        foreach ($lines as $line) {
            $line->updateQuietly(['line_discount_amount' => 0, 'line_total_amount' => $line->line_subtotal_amount]);
        }

        $discounts = collect();

        if ($checkout->discount_code !== null) {
            $discount = Discount::withoutGlobalScopes()->where('store_id', $checkout->store_id)->whereRaw('lower(code) = ?', [strtolower($checkout->discount_code)])->first();

            if ($discount?->isAvailable()) {
                $discounts->push($discount);
            }
        }

        Discount::withoutGlobalScopes()
            ->where('store_id', $checkout->store_id)
            ->where('type', DiscountType::Automatic)
            ->get()
            ->filter(fn (Discount $discount): bool => $discount->isAvailable())
            ->each(fn (Discount $discount): mixed => $discounts->push($discount));

        foreach ($discounts as $discount) {
            $currentSubtotal = (int) $lines->sum('line_total_amount');
            $minimum = (int) ($discount->rules_json['min_purchase_amount'] ?? $discount->rules_json['minimum_purchase_amount'] ?? 0);

            if ($currentSubtotal < $minimum) {
                continue;
            }

            $result = $this->discounts->calculate($discount, $currentSubtotal, $lines->map(fn ($line): array => [
                'line_id' => $line->id,
                'amount' => $line->line_total_amount,
                'product_id' => $line->variant->product_id,
                'collection_ids' => $line->variant->product->collections->modelKeys(),
            ])->all());
            $discountAmount += $result->amount;
            $freeShipping = $freeShipping || $result->freeShipping;

            foreach ($lines as $line) {
                $lineDiscount = $result->allocations[$line->id] ?? 0;
                $line->updateQuietly([
                    'line_discount_amount' => $line->line_discount_amount + $lineDiscount,
                    'line_total_amount' => max(0, $line->line_total_amount - $lineDiscount),
                ]);
                if ($lineDiscount > 0) {
                    $discountAllocations[$line->id][] = ['discount_id' => $discount->getKey(), 'amount' => $lineDiscount];
                }
            }
        }

        $shippingAmount = $checkout->shippingRate !== null && ! $freeShipping ? ($this->shipping->calculate($checkout->shippingRate, $cart) ?? 0) : 0;
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
        $totals = [...$result->toArray(), 'discount_allocations' => $discountAllocations];
        $checkout->update([
            'totals_json' => $totals,
            'tax_provider_snapshot_json' => [
                'provider' => $taxSettings->provider,
                'mode' => $taxSettings->mode,
                'rates' => $taxSettings->rates_json ?? [],
                'captured_at' => now()->toIso8601String(),
                'tax_total' => $taxTotal,
            ],
        ]);

        return $result;
    }
}
