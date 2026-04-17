<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\InvalidCheckoutStateException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\TaxSetting;
use App\ValueObjects\DiscountCalculationResult;
use App\ValueObjects\PricingResult;
use App\ValueObjects\TaxCalculationResult;
use Illuminate\Support\Facades\DB;

final class PricingEngine
{
    public function __construct(
        private readonly DiscountService $discountService,
        private readonly ShippingCalculator $shippingCalculator,
        private readonly TaxCalculator $taxCalculator,
    ) {}

    public function calculate(Checkout $checkout): PricingResult
    {
        /** @var PricingResult $result */
        $result = DB::transaction(function () use ($checkout): PricingResult {
            $lockedCheckout = $this->lockCheckout($checkout);

            $cart = $lockedCheckout->cart;
            $this->recalculateLineSubtotals($cart);

            $subtotal = $this->subtotal($cart);
            $discountResult = $this->calculateDiscount($lockedCheckout);

            $this->discountService->applyToCartLines($cart, $discountResult);

            $shippingAmount = $this->shippingAmount($lockedCheckout, $cart, $discountResult);
            $taxResult = $this->taxResult($lockedCheckout, $cart, $shippingAmount);

            $total = max(0, ($subtotal - $discountResult->amount) + $shippingAmount + $taxResult->totalAmount);

            $pricingResult = new PricingResult(
                subtotal: $subtotal,
                discount: $discountResult->amount,
                shipping: $shippingAmount,
                taxLines: $taxResult->taxLines,
                taxTotal: $taxResult->totalAmount,
                total: $total,
                currency: (string) $cart->currency,
                lineDiscountAllocations: $discountResult->lineAllocations,
                freeShippingApplied: $discountResult->freeShipping,
            );

            $lockedCheckout->totals_json = $pricingResult->toArray();
            $lockedCheckout->tax_provider_snapshot_json = $this->buildTaxSnapshot($taxResult);
            $lockedCheckout->save();

            $this->syncCheckout($checkout, $lockedCheckout);

            return $pricingResult;
        });

        return $result;
    }

    private function calculateDiscount(Checkout $checkout): DiscountCalculationResult
    {
        $code = is_string($checkout->discount_code) ? trim($checkout->discount_code) : '';

        if ($code === '') {
            return DiscountCalculationResult::none();
        }

        $discount = $this->discountService->validate($code, $checkout->store, $checkout->cart);

        return $this->discountService->calculate($discount, $checkout->cart);
    }

    private function shippingAmount(Checkout $checkout, Cart $cart, DiscountCalculationResult $discountResult): int
    {
        if (! $this->shippingCalculator->cartRequiresShipping($cart)) {
            return 0;
        }

        if ($discountResult->freeShipping) {
            return 0;
        }

        if ($checkout->shipping_method_id === null) {
            return 0;
        }

        $address = $checkout->shipping_address_json;

        if (! is_array($address)) {
            throw InvalidCheckoutStateException::shippingAddressRequired((int) $checkout->id);
        }

        $quote = $this->shippingCalculator->selectActiveRateByZone(
            store: $checkout->store,
            address: $address,
            cart: $cart,
            shippingRateId: (int) $checkout->shipping_method_id,
            checkoutId: (int) $checkout->id,
        );

        return $quote->amount;
    }

    private function taxResult(Checkout $checkout, Cart $cart, int $shippingAmount): TaxCalculationResult
    {
        /** @var TaxSetting|null $taxSetting */
        $taxSetting = TaxSetting::query()->find((int) $checkout->store_id);

        if ($taxSetting === null) {
            return TaxCalculationResult::zero();
        }

        $address = is_array($checkout->shipping_address_json) ? $checkout->shipping_address_json : [];
        $lineAmounts = $cart->lines
            ->sortBy('id')
            ->map(static fn (CartLine $line): int => (int) $line->line_total_amount)
            ->values()
            ->all();

        return $this->taxCalculator->calculateForAmounts(
            lineAmounts: $lineAmounts,
            shippingAmount: $shippingAmount,
            settings: $taxSetting,
            address: $address,
        );
    }

    private function subtotal(Cart $cart): int
    {
        return (int) $cart->lines->sum(static fn (CartLine $line): int => (int) $line->line_subtotal_amount);
    }

    private function recalculateLineSubtotals(Cart $cart): void
    {
        $cart->loadMissing('lines');

        /** @var CartLine $line */
        foreach ($cart->lines as $line) {
            $lineSubtotal = (int) $line->unit_price_amount * (int) $line->quantity;
            $lineDiscount = max(0, min($lineSubtotal, (int) $line->line_discount_amount));
            $lineTotal = $lineSubtotal - $lineDiscount;

            $line->line_subtotal_amount = $lineSubtotal;
            $line->line_discount_amount = $lineDiscount;
            $line->line_total_amount = $lineTotal;
            $line->save();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildTaxSnapshot(TaxCalculationResult $result): array
    {
        return [
            'provider' => 'manual',
            'calculated_at' => now()->toISOString(),
            'rate_basis_points' => $result->rateBasisPoints,
            'lines' => array_map(static fn ($line): array => $line->toArray(), $result->taxLines),
            'tax_total' => $result->totalAmount,
        ];
    }

    private function lockCheckout(Checkout $checkout): Checkout
    {
        /** @var Checkout $locked */
        $locked = Checkout::query()
            ->with(['store', 'cart.lines.variant.product.collections'])
            ->whereKey($checkout->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        return $locked;
    }

    private function syncCheckout(Checkout $target, Checkout $source): void
    {
        $target->status = $source->status;
        $target->payment_method = $source->payment_method;
        $target->email = $source->email;
        $target->shipping_address_json = $source->shipping_address_json;
        $target->billing_address_json = $source->billing_address_json;
        $target->shipping_method_id = $source->shipping_method_id;
        $target->discount_code = $source->discount_code;
        $target->tax_provider_snapshot_json = $source->tax_provider_snapshot_json;
        $target->totals_json = $source->totals_json;
        $target->expires_at = $source->expires_at;
        $target->updated_at = $source->updated_at;
    }
}
