<?php

namespace App\Services;

use App\Exceptions\InvalidDiscountException;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\ShippingRate;
use App\Models\TaxSettings;
use App\ValueObjects\PricingResult;
use App\ValueObjects\TaxCalculationRequest;

/**
 * The deterministic pricing pipeline.
 *
 * For a given checkout it computes, in strict order: line subtotals -> cart
 * subtotal -> discount (allocated per line) -> discounted subtotal -> shipping
 * -> tax (on discounted line amounts plus taxable shipping) -> total. The same
 * inputs always produce the same {@see PricingResult}, which is snapshotted to
 * `checkouts.totals_json`.
 *
 * All amounts are integers in minor units (cents).
 */
class PricingEngine
{
    public function __construct(
        private readonly DiscountService $discounts,
        private readonly ShippingCalculator $shipping,
        private readonly TaxCalculator $tax,
    ) {}

    /**
     * Calculate and snapshot totals for a checkout.
     */
    public function calculate(Checkout $checkout): PricingResult
    {
        $cart = $checkout->cart->loadMissing('lines.variant.product');
        $lines = $cart->lines;

        // Step 1-2: line subtotals and cart subtotal.
        $subtotal = (int) $lines->sum('line_subtotal_amount');

        // Step 3: discount (allocated per line so post-discount line amounts are
        // available for tax).
        [$discountAmount, $freeShipping, $allocations] = $this->resolveDiscount($checkout, $cart, $subtotal);

        // Step 5: shipping (free-shipping discount zeroes it).
        $shipping = $this->resolveShipping($checkout, $cart);
        if ($freeShipping) {
            $shipping = 0;
        }

        // Step 6: tax on discounted line amounts plus taxable shipping.
        $taxSettings = $this->taxSettings($checkout);
        $discountedLineAmounts = $this->discountedLineAmounts($lines, $allocations);

        $taxResult = $this->tax->calculate(new TaxCalculationRequest(
            lineAmounts: $discountedLineAmounts,
            shippingAmount: $shipping,
            address: $checkout->shipping_address_json ?? [],
            taxSettings: $taxSettings,
        ));

        $taxTotal = $taxResult->totalAmount;

        // Step 7: total. `subtotal` is always the sum of line subtotals before
        // discount (gross in tax-inclusive mode, net otherwise) per the snapshot
        // contract. In tax-inclusive mode the tax already sits inside the line
        // amounts, so it is not added again to the total.
        $discountedSubtotal = $subtotal - $discountAmount;
        $total = $taxSettings->prices_include_tax
            ? $discountedSubtotal + $shipping
            : $discountedSubtotal + $shipping + $taxTotal;

        $result = new PricingResult(
            subtotal: $subtotal,
            discount: $discountAmount,
            shipping: $shipping,
            taxLines: $taxResult->taxLines,
            taxTotal: $taxTotal,
            total: $total,
            currency: $cart->currency,
        );

        $checkout->forceFill(['totals_json' => $result->toArray()])->save();

        return $result;
    }

    /**
     * Resolve the discount amount, free-shipping flag, and per-line allocations
     * for a checkout's applied code. Invalid codes contribute no discount.
     *
     * @return array{0: int, 1: bool, 2: array<int, int>}
     */
    private function resolveDiscount(Checkout $checkout, $cart, int $subtotal): array
    {
        if ($checkout->discount_code === null || $checkout->discount_code === '') {
            return [0, false, []];
        }

        $discount = $this->validateDiscount($checkout, $cart);

        if ($discount === null) {
            return [0, false, []];
        }

        $qualifying = $this->discounts->qualifyingLineSubtotals($discount, $cart);
        $result = $this->discounts->calculate($discount, $subtotal, $qualifying);

        return [$result->amount, $result->freeShipping, $result->allocations];
    }

    private function validateDiscount(Checkout $checkout, $cart): ?Discount
    {
        try {
            return $this->discounts->validate($checkout->discount_code, $checkout->store, $cart);
        } catch (InvalidDiscountException) {
            return null;
        }
    }

    private function resolveShipping(Checkout $checkout, $cart): int
    {
        if (! $this->shipping->cartRequiresShipping($cart)) {
            return 0;
        }

        if ($checkout->shipping_method_id === null) {
            return 0;
        }

        $rate = ShippingRate::query()->find($checkout->shipping_method_id);

        if ($rate === null) {
            return 0;
        }

        return $this->shipping->calculate($rate, $cart) ?? 0;
    }

    /**
     * Post-discount taxable amount per line (line subtotal minus its allocated
     * discount share).
     *
     * @param  array<int, int>  $allocations
     * @return list<int>
     */
    private function discountedLineAmounts($lines, array $allocations): array
    {
        return $lines
            ->map(fn ($line): int => $line->line_subtotal_amount - ($allocations[$line->id] ?? 0))
            ->values()
            ->all();
    }

    /**
     * The store's tax settings, or a transient zero-rate default when none are
     * configured.
     */
    private function taxSettings(Checkout $checkout): TaxSettings
    {
        $settings = TaxSettings::query()->where('store_id', $checkout->store_id)->first();

        if ($settings !== null) {
            return $settings;
        }

        return tap(new TaxSettings([
            'store_id' => $checkout->store_id,
            'mode' => 'manual',
            'provider' => 'none',
            'prices_include_tax' => false,
            'config_json' => [],
        ]))->setRelation('store', $checkout->store);
    }
}
