<?php

namespace App\Services;

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Models\Checkout;
use App\Models\Discount;
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
        $checkout->loadMissing(['cart.lines.variant.product.collections', 'shippingMethod']);
        $lines = $checkout->cart->lines;
        $subtotal = $lines->sum(fn ($line): int => $line->unit_price_amount * $line->quantity);
        $allocations = array_fill_keys($lines->pluck('id')->all(), 0);
        $discountTotal = 0;
        $freeShipping = false;
        $discounts = collect();

        if ($checkout->discount_code) {
            $discounts->push($this->discountService->validate($checkout->discount_code, $checkout->store, $checkout->cart));
        }

        $discounts = $discounts->merge(Discount::query()
            ->where('store_id', $checkout->store_id)
            ->where('type', DiscountType::Automatic)
            ->where('status', DiscountStatus::Active)
            ->where('starts_at', '<=', now())
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->get());

        foreach ($discounts as $discount) {
            $discountLines = $lines->map(function ($line) use ($allocations) {
                $line->line_subtotal_amount = ($line->unit_price_amount * $line->quantity) - $allocations[$line->id];

                return $line;
            });
            $result = $this->discountService->calculate($discount, $subtotal - $discountTotal, $discountLines);
            $discountTotal += $result->amount;
            $freeShipping = $freeShipping || $result->freeShipping;

            foreach ($result->allocations as $lineId => $amount) {
                $allocations[$lineId] += $amount;
            }
        }

        foreach ($lines as $line) {
            $line->update([
                'line_subtotal_amount' => $line->unit_price_amount * $line->quantity,
                'line_discount_amount' => $allocations[$line->id],
                'line_total_amount' => ($line->unit_price_amount * $line->quantity) - $allocations[$line->id],
            ]);
        }

        $requiresShipping = $lines->contains(fn ($line): bool => $line->variant->requires_shipping);
        $shipping = $requiresShipping && $checkout->shippingMethod
            ? ($this->shippingCalculator->calculate($checkout->shippingMethod, $checkout->cart) ?? 0)
            : 0;
        $shipping = $freeShipping ? 0 : $shipping;

        $settings = TaxSettings::query()->find($checkout->store_id);
        $taxLines = [];

        if ($settings) {
            $taxLines = $this->taxCalculator->calculateLines(
                $lines->map(fn ($line): int => $line->line_total_amount)->all(),
                $settings,
                $checkout->shipping_address_json ?? [],
            );

            if (($settings->config_json['shipping_taxable'] ?? false) && $shipping > 0) {
                $taxLines[] = $this->taxCalculator->calculate($shipping, $settings, $checkout->shipping_address_json ?? []);
            }
        }

        $taxTotal = array_sum(array_map(fn (TaxLine $line): int => $line->amount, $taxLines));
        $taxAddedToTotal = $settings?->prices_include_tax ? 0 : $taxTotal;
        $total = $subtotal - $discountTotal + $shipping + $taxAddedToTotal;
        $result = new PricingResult($subtotal, $discountTotal, $shipping, $taxLines, $taxTotal, $total, $checkout->cart->currency);

        $checkout->update(['totals_json' => $result->toArray()]);

        return $result;
    }
}
