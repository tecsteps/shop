<?php

namespace App\Services;

use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\ShippingRate;
use App\Models\Store;
use App\Models\TaxSettings;
use App\ValueObjects\DiscountResult;
use App\ValueObjects\PricingResult;

class PricingEngine
{
    public function __construct(
        private readonly DiscountService $discounts,
        private readonly ShippingCalculator $shipping,
        private readonly TaxCalculator $tax,
    ) {}

    public function calculate(Checkout $checkout): PricingResult
    {
        $cart = $checkout->cart;
        $store = $checkout->store ?? Store::query()->findOrFail($checkout->store_id);

        $lines = $cart->lines()->get();
        $lineAmounts = [];

        foreach ($lines as $line) {
            $lineAmounts[$line->getKey()] = [
                'subtotal' => (int) $line->line_subtotal_amount,
                'discount' => 0,
            ];
        }

        $subtotal = array_sum(array_column($lineAmounts, 'subtotal'));

        $discountResult = null;

        if ($checkout->discount_code !== null) {
            try {
                $discount = $this->discounts->validate($checkout->discount_code, $store, $cart);
                $discountResult = $this->discounts->calculate($discount, $cart);

                foreach ($discountResult->allocations as $allocation) {
                    if (isset($lineAmounts[$allocation['line_id']])) {
                        $lineAmounts[$allocation['line_id']]['discount'] = (int) $allocation['amount'];
                    }
                }
            } catch (InvalidDiscountException) {
                $discountResult = null;
            }
        }

        $discountTotal = array_sum(array_column($lineAmounts, 'discount'));

        $shippingAmount = $this->computeShipping($checkout, $cart, $discountResult);

        $taxAddress = $checkout->shipping_address_json ?? [];
        $settings = TaxSettings::query()->firstOrNew(['store_id' => (int) $store->getKey()]);

        $taxLineEntries = [];

        foreach ($lines as $line) {
            $lineInfo = $lineAmounts[$line->getKey()] ?? null;

            if ($lineInfo === null) {
                continue;
            }

            $taxLineEntries[] = [
                'amount' => $lineInfo['subtotal'] - $lineInfo['discount'],
                'label' => 'Tax',
            ];
        }

        $taxResult = $this->tax->calculate($taxLineEntries, $settings, $taxAddress, $shippingAmount);

        $total = ($subtotal - $discountTotal) + $shippingAmount + $taxResult['total'];

        return new PricingResult(
            subtotal: $subtotal,
            discount: $discountTotal,
            shipping: $shippingAmount,
            taxLines: $taxResult['lines'],
            taxTotal: $taxResult['total'],
            total: $total,
            currency: $cart->currency,
        );
    }

    protected function computeShipping(Checkout $checkout, Cart $cart, ?DiscountResult $discountResult): int
    {
        if ($checkout->shipping_method_id === null) {
            return 0;
        }

        $rate = ShippingRate::query()->find($checkout->shipping_method_id);

        if ($rate === null) {
            return 0;
        }

        $amount = $this->shipping->calculate($rate, $cart) ?? 0;

        if ($discountResult !== null && $discountResult->freeShipping) {
            return 0;
        }

        return $amount;
    }
}
