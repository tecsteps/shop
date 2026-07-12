<?php

namespace App\Services;

use App\Models\Checkout;
use App\Models\ShippingRate;
use App\Models\TaxSettings;
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
        $remainingAmounts = $cart->lines->mapWithKeys(fn ($line): array => [
            (int) $line->id => (int) $line->line_subtotal_amount,
        ])->all();
        $lineDiscounts = array_fill_keys(array_keys($remainingAmounts), 0);
        $appliedDiscounts = [];
        $freeShipping = false;
        $discounts = collect();

        if ($checkout->discount_code !== null && $checkout->discount_code !== '') {
            $discounts->push($this->discounts->validate($checkout->discount_code, $checkout->store, $cart));
        }
        foreach ($this->discounts->automaticDiscounts($checkout->store, $cart) as $automatic) {
            if (! $discounts->contains('id', $automatic->id)) {
                $discounts->push($automatic);
            }
        }

        foreach ($discounts as $discount) {
            $syntheticLines = $cart->lines->map(fn ($line): array => [
                'id' => (int) $line->id,
                'product_id' => (int) $line->variant->product_id,
                'product' => $line->variant->product,
                'line_subtotal_amount' => (int) ($remainingAmounts[$line->id] ?? 0),
            ]);
            $discountResult = $this->discounts->calculate($discount, array_sum($remainingAmounts), $syntheticLines);
            if ($discountResult->amount < 1 && ! $discountResult->freeShipping) {
                continue;
            }
            $allocations = [];
            foreach ($discountResult->allocations as $lineId => $allocation) {
                $allocation = min((int) ($remainingAmounts[$lineId] ?? 0), (int) $allocation);
                if ($allocation < 1) {
                    continue;
                }
                $remainingAmounts[$lineId] -= $allocation;
                $lineDiscounts[$lineId] += $allocation;
                $allocations[(int) $lineId] = $allocation;
            }
            $freeShipping = $freeShipping || $discountResult->freeShipping;
            $appliedDiscounts[] = [
                'discount_id' => (int) $discount->id,
                'type' => $discount->type instanceof \BackedEnum ? $discount->type->value : (string) $discount->type,
                'code' => $discount->code,
                'amount' => array_sum($allocations),
                'free_shipping' => $discountResult->freeShipping,
                'allocations' => $allocations,
            ];
        }
        $this->applyAllocations($cart->lines, $lineDiscounts);
        $discountAmount = array_sum($lineDiscounts);

        $shipping = 0;
        if ($checkout->shipping_method_id !== null && $this->shipping->requiresShipping($cart)) {
            $rate = ShippingRate::withoutGlobalScopes()
                ->whereHas('zone', fn ($zones) => $zones->withoutGlobalScopes()->where('store_id', $checkout->store_id))
                ->find($checkout->shipping_method_id);
            $shipping = $rate === null ? 0 : (int) ($this->shipping->calculate($rate, $cart) ?? 0);
        }
        if ($freeShipping) {
            $shipping = 0;
        }

        $lineAmounts = $cart->lines->map(fn ($line): int => (int) ($remainingAmounts[$line->id] ?? 0))->all();
        $settings = TaxSettings::withoutGlobalScopes()->where('store_id', $checkout->store_id)->first();
        $taxAddress = (array) ($checkout->shipping_address_json ?? $checkout->billing_address_json ?? []);
        $taxResult = $this->taxes->calculateLines($lineAmounts, $shipping, $settings, $taxAddress);
        $inclusive = (bool) ($settings?->prices_include_tax ?? false);
        $discountedSubtotal = max(0, $subtotal - $discountAmount);
        $total = $discountedSubtotal + $shipping + ($inclusive ? 0 : $taxResult->totalAmount);

        $taxDefinition = $taxResult->taxLines[0] ?? null;
        $jurisdiction = strtoupper((string) ($taxAddress['country_code'] ?? $taxAddress['country'] ?? ''));
        $lineTaxAllocations = [];
        $taxSnapshotLines = [];
        foreach ($cart->lines->values() as $index => $line) {
            $taxAmount = (int) ($taxResult->lineAmounts[$index] ?? 0);
            $lineTaxAllocations[(int) $line->id] = $taxDefinition === null || $taxAmount < 1 ? [] : [[
                'name' => $taxDefinition->name,
                'rate' => $taxDefinition->rate,
                'amount' => $taxAmount,
            ]];
            $taxSnapshotLines[] = [
                'cart_line_id' => (int) $line->id,
                'variant_id' => (int) $line->variant_id,
                'tax_amount' => $taxAmount,
                'rate' => (int) ($taxDefinition?->rate ?? 0),
                'jurisdiction' => $jurisdiction,
            ];
        }

        $result = new PricingResult(
            subtotal: $subtotal,
            discount: $discountAmount,
            shipping: $shipping,
            taxLines: $taxResult->taxLines,
            taxTotal: $taxResult->totalAmount,
            total: $total,
            currency: (string) $cart->currency,
        );

        $checkout->tax_provider_snapshot_json = [
            'provider' => $taxResult->provider,
            'calculated_at' => now()->toIso8601String(),
            'lines' => $taxSnapshotLines,
            'shipping_tax_amount' => $taxResult->shippingAmount,
            'shipping_tax_rate' => (int) ($taxDefinition?->rate ?? 0),
            'response' => $taxResult->providerResponse,
        ];
        $checkout->totals_json = [
            ...$result->toArray(),
            'applied_discounts' => $appliedDiscounts,
            'line_tax_allocations' => $lineTaxAllocations,
        ];
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

    /** @param array<int, int> $allocations */
    private function applyAllocations(iterable $lines, array $allocations): void
    {
        foreach ($lines as $line) {
            $allocation = (int) ($allocations[$line->id] ?? 0);
            $line->update([
                'line_discount_amount' => $allocation,
                'line_total_amount' => max(0, (int) $line->line_subtotal_amount - $allocation),
            ]);
        }
    }
}
