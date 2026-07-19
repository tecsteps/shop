<?php

namespace App\Services;

use App\Enums\DiscountValueType;
use App\Models\Discount;
use App\Models\TaxSettings;
use App\ValueObjects\Address;
use App\ValueObjects\PricingResult;
use App\ValueObjects\ShippingRateVO;
use App\ValueObjects\TaxCalculationRequest;
use App\ValueObjects\TaxCalculationResult;

/**
 * Deterministic pricing pipeline (spec 05 §5):
 * line subtotals -> cart subtotal -> discounts -> discounted subtotal ->
 * shipping -> tax -> total. The same inputs always produce the same output.
 *
 * Discount-before-tax semantics (spec 05 §8.5): for tax-inclusive stores the
 * discount is subtracted from the gross subtotal and tax is extracted from
 * the post-discount gross amounts.
 */
class PricingEngine
{
    public function __construct(
        private DiscountService $discounts,
        private TaxCalculator $taxCalculator,
    ) {}

    /**
     * Run the full pricing pipeline.
     *
     * @param  array<int, array{variant_id?: int|null, product_id?: int|null, collection_ids?: array<int>, quantity: int, unit_price_amount: int, requires_shipping?: bool}>  $lines
     * @param  array<int, Discount>  $automaticDiscounts  stacked sequentially after the code discount
     */
    public function calculate(
        array $lines,
        ?Discount $codeDiscount,
        array $automaticDiscounts,
        ?ShippingRateVO $shippingRate,
        TaxSettings $taxSettings,
        ?Address $address,
        string $currency = 'USD',
    ): PricingResult {
        // Steps 1-2: line subtotals and cart subtotal.
        $lineSubtotals = [];
        $subtotal = 0;

        foreach ($lines as $index => $line) {
            $lineSubtotal = $line['unit_price_amount'] * $line['quantity'];
            $lineSubtotals[$index] = $lineSubtotal;
            $subtotal += $lineSubtotal;
        }

        // Step 3: discounts. The code discount applies first; automatic
        // discounts then stack sequentially on the remaining undiscounted
        // amount of each line.
        $lineDiscounts = array_fill_keys(array_keys($lines), 0);
        $freeShippingApplied = false;

        $appliedDiscounts = array_values(array_filter(
            array_merge([$codeDiscount], $automaticDiscounts)
        ));

        foreach ($appliedDiscounts as $discount) {
            if ($discount->value_type === DiscountValueType::FreeShipping) {
                $freeShippingApplied = true;

                continue;
            }

            $remainingLines = [];

            foreach ($lines as $index => $line) {
                $remainingLines[$index] = [
                    'product_id' => $line['product_id'] ?? null,
                    'collection_ids' => $line['collection_ids'] ?? [],
                    'line_subtotal_amount' => $lineSubtotals[$index] - $lineDiscounts[$index],
                ];
            }

            $result = $this->discounts->calculate($discount, $subtotal, $remainingLines);

            foreach ($result['allocations'] as $index => $amount) {
                $lineDiscounts[$index] += $amount;
            }
        }

        $discountTotal = array_sum($lineDiscounts);

        // Step 4: discounted subtotal.
        $discountedSubtotal = $subtotal - $discountTotal;

        // Step 5: shipping (zeroed by free-shipping discounts; digital-only
        // carts never pay shipping).
        $requiresShipping = collect($lines)->contains(
            fn (array $line): bool => (bool) ($line['requires_shipping'] ?? false)
        );

        $shipping = $requiresShipping ? ($shippingRate?->amount ?? 0) : 0;

        if ($freeShippingApplied) {
            $shipping = 0;
        }

        // Step 6: tax on discounted line amounts plus shipping. Without an
        // address no tax is calculated yet.
        $taxResult = $address === null
            ? TaxCalculationResult::zero()
            : $this->taxCalculator->calculate(new TaxCalculationRequest(
                lineItems: collect($lines)->map(fn (array $line, int $index): array => [
                    'variant_id' => $line['variant_id'] ?? null,
                    'amount' => $lineSubtotals[$index] - $lineDiscounts[$index],
                ])->values()->all(),
                shippingAmount: $shipping,
                address: $address,
                taxSettings: $taxSettings,
            ));

        // Step 7: total. Tax-inclusive prices already contain the tax.
        $total = $discountedSubtotal + $shipping
            + ($taxSettings->prices_include_tax ? 0 : $taxResult->totalAmount);

        return new PricingResult(
            subtotal: $subtotal,
            discount: $discountTotal,
            shipping: $shipping,
            taxLines: $taxResult->taxLines,
            taxTotal: $taxResult->totalAmount,
            total: $total,
            currency: $currency,
            lineDiscounts: $lineDiscounts,
        );
    }
}
