<?php

namespace App\Services;

use App\Enums\DiscountValueType;
use App\Models\Checkout;
use App\Models\ShippingRate;
use App\Models\TaxSetting;
use App\ValueObjects\PricingResult;
use App\ValueObjects\TaxLine;

/**
 * Deterministic pricing pipeline per spec 05 section 5.
 * All amounts are integers in minor units (cents).
 */
class PricingEngine
{
    public function __construct(
        protected DiscountService $discounts,
        protected ShippingCalculator $shipping,
        protected TaxCalculator $tax,
    ) {}

    public function lineSubtotal(int $unitPriceAmount, int $quantity): int
    {
        return $unitPriceAmount * $quantity;
    }

    /**
     * @param  array<int, int>  $amounts
     */
    public function sumSubtotals(array $amounts): int
    {
        return array_sum($amounts);
    }

    public function percentDiscount(int $subtotal, int $percent): int
    {
        if ($subtotal <= 0 || $percent <= 0) {
            return 0;
        }

        return intdiv($subtotal * $percent, 100);
    }

    public function fixedDiscount(int $subtotal, int $amount): int
    {
        if ($subtotal <= 0 || $amount <= 0) {
            return 0;
        }

        return min($amount, $subtotal);
    }

    public function discountedSubtotal(int $subtotal, int $discount): int
    {
        return max(0, $subtotal - $discount);
    }

    public function total(int $discountedSubtotal, int $shipping, int $taxTotal, bool $pricesIncludeTax): int
    {
        if ($pricesIncludeTax) {
            return $discountedSubtotal + $shipping;
        }

        return $discountedSubtotal + $shipping + $taxTotal;
    }

    public function calculate(Checkout $checkout): PricingResult
    {
        $cart = $checkout->cart;
        $store = $checkout->store;
        $lines = $cart->cartLines()->with('variant')->orderBy('id')->get();

        $subtotals = [];
        $remaining = [];

        foreach ($lines as $line) {
            $subtotal = $this->lineSubtotal($line->unit_price_amount, $line->quantity);
            $subtotals[$line->getKey()] = $subtotal;
            $remaining[$line->getKey()] = $subtotal;
        }

        $subtotal = $this->sumSubtotals(array_values($subtotals));

        $lineDiscounts = array_fill_keys(array_keys($subtotals), 0);
        $lineAllocations = [];
        $appliedDiscounts = [];
        $freeShipping = false;

        $discountQueue = [];

        if ($checkout->discount_code !== null && $checkout->discount_code !== '') {
            $validation = $this->discounts->validate($checkout->discount_code, $store, $cart);

            if ($validation->valid && $validation->discount !== null) {
                $discountQueue[] = $validation->discount;
            }
        }

        foreach ($this->discounts->applicableAutomaticDiscounts($store, $cart) as $automatic) {
            $discountQueue[] = $automatic;
        }

        foreach ($discountQueue as $discount) {
            if ($discount->value_type === DiscountValueType::FreeShipping) {
                $freeShipping = true;
                $appliedDiscounts[] = $this->describeDiscount($discount, 0, $cart->currency);

                continue;
            }

            $input = [];

            foreach ($lines as $line) {
                $input[] = [
                    'line_id' => $line->getKey(),
                    'subtotal' => $remaining[$line->getKey()],
                    'product_id' => $line->variant->product_id,
                ];
            }

            $result = $this->discounts->calculate($discount, $subtotal, $input);

            foreach ($result['allocations'] as $lineId => $amount) {
                $lineDiscounts[$lineId] += $amount;
                $remaining[$lineId] = max(0, $remaining[$lineId] - $amount);
                $lineAllocations[$lineId][] = [
                    'discount_id' => $discount->getKey(),
                    'amount' => $amount,
                ];
            }

            $appliedDiscounts[] = $this->describeDiscount($discount, $result['total'], $cart->currency);
        }

        $discountTotal = $this->sumSubtotals(array_values($lineDiscounts));
        $discountedSubtotal = $this->discountedSubtotal($subtotal, $discountTotal);

        foreach ($lines as $line) {
            $line->line_subtotal_amount = $subtotals[$line->getKey()];
            $line->line_discount_amount = $lineDiscounts[$line->getKey()];
            $line->line_total_amount = $remaining[$line->getKey()];
            $line->save();
        }

        $shippingAmount = $this->resolveShippingAmount($checkout, $freeShipping);

        $address = (array) ($checkout->shipping_address_json ?? []);
        $lineItems = [];

        foreach ($lines as $line) {
            $lineItems[] = [
                'line_id' => $line->getKey(),
                'variant_id' => $line->variant_id,
                'amount' => $remaining[$line->getKey()],
            ];
        }

        $taxResult = $this->tax->calculate($store, $lineItems, $shippingAmount, $address);

        $settings = TaxSetting::query()->where('store_id', $store->getKey())->first();
        $pricesIncludeTax = $settings !== null && (bool) $settings->prices_include_tax;

        $total = $this->total($discountedSubtotal, $shippingAmount, $taxResult->totalAmount, $pricesIncludeTax);

        $result = new PricingResult(
            subtotal: $subtotal,
            discount: $discountTotal,
            shipping: $shippingAmount,
            taxLines: $taxResult->taxLines,
            taxTotal: $taxResult->totalAmount,
            total: $total,
            currency: $cart->currency,
            appliedDiscounts: $appliedDiscounts,
        );

        $this->storeSnapshots($checkout, $result, $lineAllocations, $lineItems, $taxResult->taxLines, $shippingAmount, $address);

        return $result;
    }

    protected function resolveShippingAmount(Checkout $checkout, bool $freeShipping): int
    {
        $cart = $checkout->cart;

        if (! $this->shipping->requiresShipping($cart)) {
            return 0;
        }

        if ($freeShipping) {
            return 0;
        }

        if ($checkout->shipping_method_id === null) {
            return 0;
        }

        $rate = ShippingRate::query()->whereKey($checkout->shipping_method_id)->first();

        if ($rate === null || ! $rate->is_active) {
            return 0;
        }

        return $this->shipping->calculate($rate, $cart) ?? 0;
    }

    /**
     * @param  array<int, array<int, array{discount_id: int, amount: int}>>  $lineAllocations
     * @param  array<int, array{line_id: int, variant_id: ?int, amount: int}>  $lineItems
     * @param  array<int, TaxLine>  $taxLines
     * @param  array<string, mixed>  $address
     */
    protected function storeSnapshots(
        Checkout $checkout,
        PricingResult $result,
        array $lineAllocations,
        array $lineItems,
        array $taxLines,
        int $shippingAmount,
        array $address,
    ): void {
        $totals = $result->toArray();
        $totals['discount_allocations'] = $lineAllocations;

        $checkout->totals_json = $totals;

        $lineTaxes = [];
        $lineCount = count($lineItems);

        foreach ($lineItems as $index => $item) {
            $taxLine = $taxLines[$index] ?? null;

            $lineTaxes[] = [
                'line_id' => $item['line_id'],
                'variant_id' => $item['variant_id'],
                'tax_amount' => $taxLine?->amount ?? 0,
                'rate' => $taxLine?->rate ?? 0,
                'jurisdiction' => strtoupper((string) ($address['country_code'] ?? $address['country'] ?? '')),
            ];
        }

        $shippingTaxLine = $taxLines[$lineCount] ?? null;

        $checkout->tax_provider_snapshot_json = [
            'provider' => 'manual',
            'calculated_at' => now()->toIso8601String(),
            'lines' => $lineTaxes,
            'shipping_tax_amount' => $shippingTaxLine?->amount ?? 0,
            'shipping_tax_rate' => $shippingTaxLine?->rate ?? 0,
        ];

        $checkout->save();
    }

    /**
     * @return array<string, mixed>
     */
    protected function describeDiscount(\App\Models\Discount $discount, int $appliedAmount, string $currency): array
    {
        return [
            'discount_id' => $discount->getKey(),
            'code' => $discount->code,
            'type' => $discount->value_type->value,
            'value_amount' => $discount->value_amount,
            'applied_amount' => $appliedAmount,
            'description' => $this->discountDescription($discount, $currency),
        ];
    }

    protected function discountDescription(\App\Models\Discount $discount, string $currency): string
    {
        return match ($discount->value_type) {
            DiscountValueType::Percent => "{$discount->value_amount}% off your order",
            DiscountValueType::Fixed => number_format($discount->value_amount / 100, 2).' '.$currency.' off your order',
            DiscountValueType::FreeShipping => 'Free shipping on your order',
        };
    }
}
