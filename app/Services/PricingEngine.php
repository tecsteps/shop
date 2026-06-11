<?php

namespace App\Services;

use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\ShippingRate;
use App\Models\TaxSettings;
use App\ValueObjects\DiscountResult;
use App\ValueObjects\PricingResult;

class PricingEngine
{
    public function __construct(
        protected DiscountService $discountService,
        protected ShippingCalculator $shippingCalculator,
        protected TaxCalculator $taxCalculator,
    ) {}

    /**
     * Run the deterministic pricing pipeline (spec 05 section 5.1):
     * subtotal -> discount -> discounted subtotal -> shipping -> tax -> total.
     * The result is snapshotted to checkouts.totals_json.
     */
    public function calculate(Checkout $checkout): PricingResult
    {
        $cart = Cart::query()->withoutGlobalScopes()->findOrFail($checkout->cart_id);
        $lines = $cart->lines()->with('variant.product')->get()->all();

        $subtotal = 0;

        foreach ($lines as $line) {
            $subtotal += $line->line_subtotal_amount;
        }

        $discountResult = $this->resolveDiscount($checkout, $cart, $subtotal, $lines);
        $this->applyDiscountAllocations($lines, $discountResult);

        $discountedSubtotal = max(0, $subtotal - $discountResult->amount);

        $shipping = $this->resolveShipping($checkout, $cart, $discountResult->freeShipping);

        $settings = TaxSettings::query()->where('store_id', $checkout->store_id)->first();

        $taxableBase = $discountedSubtotal
            + (($settings?->shippingTaxable() ?? true) ? $shipping : 0);

        $taxResult = $this->taxCalculator->calculate($taxableBase, $settings, $checkout->shipping_address_json ?? []);

        $total = $settings?->prices_include_tax === true
            ? $discountedSubtotal + $shipping
            : $discountedSubtotal + $shipping + $taxResult->taxTotal;

        $result = new PricingResult(
            subtotal: $subtotal,
            discount: $discountResult->amount,
            shipping: $shipping,
            taxLines: $taxResult->taxLines,
            taxTotal: $taxResult->taxTotal,
            total: $total,
            currency: $cart->currency,
        );

        $checkout->forceFill(['totals_json' => $result->toArray()])->save();

        return $result;
    }

    /**
     * Validate and calculate the checkout's discount code. A code that became
     * invalid since it was applied yields no discount rather than an error.
     *
     * @param  list<CartLine>  $lines
     */
    protected function resolveDiscount(Checkout $checkout, Cart $cart, int $subtotal, array $lines): DiscountResult
    {
        if (blank($checkout->discount_code) || $subtotal <= 0) {
            return DiscountResult::none();
        }

        try {
            $discount = $this->discountService->validate($checkout->discount_code, $checkout->store, $cart);
        } catch (InvalidDiscountException) {
            return DiscountResult::none();
        }

        return $this->discountService->calculate($discount, $subtotal, $lines);
    }

    /**
     * Persist per-line discount allocations so each line carries its rounded
     * share (spec 05 section 5.3).
     *
     * @param  list<CartLine>  $lines
     */
    protected function applyDiscountAllocations(array $lines, DiscountResult $discountResult): void
    {
        foreach ($lines as $line) {
            $allocation = $discountResult->allocations[$line->getKey()] ?? 0;

            if ($line->line_discount_amount === $allocation) {
                continue;
            }

            $line->line_discount_amount = $allocation;
            $line->line_total_amount = $line->line_subtotal_amount - $allocation;
            $line->save();
        }
    }

    /**
     * Shipping per the selected rate; zero when nothing requires shipping,
     * no rate is selected yet, or a free shipping discount applies.
     */
    protected function resolveShipping(Checkout $checkout, Cart $cart, bool $freeShipping): int
    {
        if ($freeShipping || ! $cart->requiresShipping() || $checkout->shipping_method_id === null) {
            return 0;
        }

        $rate = ShippingRate::query()->find($checkout->shipping_method_id);

        if ($rate === null) {
            return 0;
        }

        return $this->shippingCalculator->calculate($rate, $cart);
    }
}
