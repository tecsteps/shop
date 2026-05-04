<?php

namespace App\Services;

use App\Exceptions\InvalidCheckoutTransitionException;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\ShippingRate;
use App\Models\TaxSettings;
use App\ValueObjects\DiscountResult;
use App\ValueObjects\PricingResult;
use Illuminate\Support\Facades\DB;

class PricingEngine
{
    public function __construct(
        private readonly DiscountService $discounts,
        private readonly ShippingCalculator $shipping,
        private readonly TaxCalculator $taxes,
    ) {}

    public function calculate(Checkout $checkout): PricingResult
    {
        return DB::transaction(function () use ($checkout): PricingResult {
            $checkout = Checkout::withoutGlobalScopes()
                ->with(['cart.lines'])
                ->whereKey($checkout->getKey())
                ->firstOrFail();
            $cart = $checkout->cart;

            $this->resetLineAmounts($checkout);

            $lines = CartLine::withoutGlobalScopes()
                ->where('cart_id', $cart->getKey())
                ->orderBy('id')
                ->get();
            $subtotal = $lines->sum('line_subtotal_amount');
            $discountAmount = 0;
            $freeShipping = false;

            if ($checkout->discount_code) {
                $discount = $this->discounts->validate($checkout->discount_code, $checkout->store, $cart);
                $discountResult = $this->discounts->applyToCart($cart, $discount);
                $discountAmount += $discountResult->amount;
                $freeShipping = $freeShipping || $discountResult->freeShipping;
            }

            foreach ($this->discounts->automaticForCart($checkout->store, $cart) as $discount) {
                $discountResult = $this->discounts->applyToCart($cart, $discount);
                $discountAmount += $discountResult->amount;
                $freeShipping = $freeShipping || $discountResult->freeShipping;
            }

            $lines = CartLine::withoutGlobalScopes()
                ->where('cart_id', $cart->getKey())
                ->orderBy('id')
                ->get();
            $discountResult = new DiscountResult($discountAmount, [], $freeShipping);
            $shippingAmount = $this->shippingAmount($checkout, $discountResult);
            $taxSettings = $this->taxSettings($checkout);
            $taxResult = $this->taxes->calculateForAmounts(
                $lines->pluck('line_total_amount')->map(fn (int $amount): int => $amount)->all(),
                $shippingAmount,
                $taxSettings,
                $checkout->shipping_address_json ?? [],
            );
            $discountedSubtotal = $subtotal - $discountResult->amount;
            $total = $taxSettings->prices_include_tax
                ? $discountedSubtotal + $shippingAmount
                : $discountedSubtotal + $shippingAmount + $taxResult->totalAmount;

            $result = new PricingResult(
                subtotal: $subtotal,
                discount: $discountResult->amount,
                shipping: $shippingAmount,
                taxLines: $taxResult->taxLines,
                taxTotal: $taxResult->totalAmount,
                total: $total,
                currency: $cart->currency,
            );

            $checkout->forceFill([
                'tax_provider_snapshot_json' => $taxResult->toArray(),
                'totals_json' => $result->toArray(),
            ])->save();

            return $result;
        });
    }

    private function resetLineAmounts(Checkout $checkout): void
    {
        CartLine::withoutGlobalScopes()
            ->where('cart_id', $checkout->cart_id)
            ->get()
            ->each(function (CartLine $line): void {
                $subtotal = $line->unit_price_amount * $line->quantity;

                $line->forceFill([
                    'line_subtotal_amount' => $subtotal,
                    'line_discount_amount' => 0,
                    'line_total_amount' => $subtotal,
                ])->save();
            });
    }

    private function shippingAmount(Checkout $checkout, DiscountResult $discountResult): int
    {
        if (! $this->shipping->requiresShipping($checkout->cart)) {
            return 0;
        }

        if ($checkout->shipping_method_id === null) {
            return 0;
        }

        $rate = ShippingRate::withoutGlobalScopes()->find($checkout->shipping_method_id);

        if (! $rate instanceof ShippingRate) {
            throw InvalidCheckoutTransitionException::because('Shipping rate is not available.');
        }

        $amount = $this->shipping->calculate($rate, $checkout->cart);

        if ($amount === null) {
            throw InvalidCheckoutTransitionException::because('Shipping rate is not available for this cart.');
        }

        return $discountResult->freeShipping ? 0 : $amount;
    }

    private function taxSettings(Checkout $checkout): TaxSettings
    {
        return TaxSettings::withoutGlobalScopes()
            ->where('store_id', $checkout->store_id)
            ->first() ?? new TaxSettings([
                'store_id' => $checkout->store_id,
                'mode' => 'manual',
                'provider' => 'none',
                'prices_include_tax' => false,
                'config_json' => [
                    'default_rate_bps' => 0,
                    'shipping_taxable' => false,
                ],
            ]);
    }
}
