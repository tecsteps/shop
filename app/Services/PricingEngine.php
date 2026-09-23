<?php

namespace App\Services;

use App\Models\Discount;
use App\ValueObjects\PricingResult;

class PricingEngine
{
    public function __construct(
        private readonly DiscountCalculator $discounts,
        private readonly TaxCalculator $taxes,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $lines
     * @param  Discount|list<Discount>|null  $discount
     */
    public function calculate(
        array $lines,
        int $shippingAmount = 0,
        Discount|array|null $discount = null,
        int $taxRate = 0,
        bool $pricesIncludeTax = false,
        bool $shippingTaxable = false,
        string $currency = 'USD',
        bool $hasCustomer = false,
        ?int $customerId = null,
        ?string $customerEmail = null,
    ): PricingResult {
        $normalized = array_map(static function (array $line): array {
            $quantity = max(1, (int) ($line['quantity'] ?? 1));
            $unitPrice = max(0, (int) ($line['unit_price_amount'] ?? 0));

            return [...$line, 'quantity' => $quantity, 'unit_price_amount' => $unitPrice, 'line_subtotal_amount' => $quantity * $unitPrice];
        }, $lines);
        $subtotal = array_sum(array_column($normalized, 'line_subtotal_amount'));
        $discounts = $discount instanceof Discount ? [$discount] : ($discount ?? []);
        $discountResult = $discounts !== []
            ? $this->discounts->calculateStacked(
                $discounts,
                $normalized,
                $subtotal,
                $customerId,
                $customerEmail,
                $hasCustomer || $customerId !== null,
            )
            : ['amount' => 0, 'allocations' => [], 'applied_discounts' => []];
        $discountedLineAmounts = [];

        foreach ($normalized as $line) {
            $key = (int) ($line['variant_id'] ?? $line['id'] ?? 0);
            $discountedLineAmounts[] = max(0, $line['line_subtotal_amount'] - (int) ($discountResult['allocations'][$key] ?? 0));
        }

        $freeShipping = collect($discountResult['applied_discounts'])->contains(static fn (array $applied): bool => $applied['type'] === 'free_shipping');
        $shipping = $freeShipping ? 0 : max(0, $shippingAmount);
        $taxLines = $this->taxes->calculate($discountedLineAmounts, $shipping, $taxRate, $shippingTaxable, $pricesIncludeTax);
        $taxTotal = array_sum(array_map(static fn ($line): int => $line->amount, $taxLines));
        $total = max(0, $subtotal - $discountResult['amount'] + $shipping + ($pricesIncludeTax ? 0 : $taxTotal));

        return new PricingResult(
            $subtotal,
            $discountResult['amount'],
            $shipping,
            $taxLines,
            $taxTotal,
            $total,
            $currency,
            $discountResult['allocations'],
            $discountResult['applied_discounts'],
        );
    }
}
