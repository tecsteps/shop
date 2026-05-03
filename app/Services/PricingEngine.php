<?php

namespace App\Services;

use App\Enums\TaxMode;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\ShippingRate;
use App\Models\TaxSettings;
use App\ValueObjects\PricingResult;
use App\ValueObjects\TaxLine;

class PricingEngine
{
    public function __construct(
        private readonly DiscountService $discounts,
        private readonly ShippingCalculator $shipping,
        private readonly TaxCalculator $taxes,
    ) {}

    public function calculate(Checkout $checkout): PricingResult
    {
        $checkout->loadMissing('store', 'cart.lines.variant.product.collections', 'shippingRate');
        $cart = $checkout->cart;
        $cart->loadMissing('lines.variant.product.collections');

        $subtotal = (int) $cart->lines->sum('line_subtotal_amount');
        $discountAmount = 0;
        $freeShipping = false;

        if ($checkout->discount_code !== null) {
            $discount = $this->discounts->validate($checkout->discount_code, $checkout->store, $cart);
            $discountResult = $this->discounts->calculate($discount, $subtotal, $cart->lines);
            $discountAmount = $discountResult->amount;
            $freeShipping = $discountResult->freeShipping;
            $this->applyLineDiscounts($cart->lines, $discountResult->allocations);
        } else {
            $this->applyLineDiscounts($cart->lines, []);
        }

        $cart->refresh()->load('lines.variant.product.collections');
        $discountedSubtotal = (int) $cart->lines->sum('line_total_amount');
        $shippingAmount = $this->shippingAmount($checkout);

        if ($freeShipping) {
            $shippingAmount = 0;
        }

        $settings = $this->taxSettings($checkout);
        $taxLines = $this->taxLines($checkout, $settings, $discountedSubtotal, $shippingAmount);
        $taxTotal = array_sum(array_map(fn (TaxLine $line): int => $line->amount, $taxLines));
        $total = $settings->prices_include_tax
            ? $discountedSubtotal + $shippingAmount
            : $discountedSubtotal + $shippingAmount + $taxTotal;

        $result = new PricingResult(
            subtotal: $subtotal,
            discount: $discountAmount,
            shipping: $shippingAmount,
            taxLines: $taxLines,
            taxTotal: $taxTotal,
            total: $total,
            currency: $cart->currency,
        );

        $checkout->forceFill([
            'totals_json' => $result->toArray(),
            'tax_provider_snapshot_json' => [
                'provider' => $settings->mode === TaxMode::Manual ? 'manual' : $settings->provider,
                'calculated_at' => now()->toISOString(),
                'lines' => array_map(fn (TaxLine $line): array => $line->toArray(), $taxLines),
            ],
        ])->save();

        return $result;
    }

    /**
     * @param  iterable<CartLine>  $lines
     * @param  array<int, int>  $allocations
     */
    private function applyLineDiscounts(iterable $lines, array $allocations): void
    {
        foreach ($lines as $line) {
            $discount = min($allocations[$line->id] ?? 0, $line->line_subtotal_amount);

            $line->forceFill([
                'line_discount_amount' => $discount,
                'line_total_amount' => $line->line_subtotal_amount - $discount,
            ])->save();
        }
    }

    private function shippingAmount(Checkout $checkout): int
    {
        if (! $this->shipping->requiresShipping($checkout->cart)) {
            return 0;
        }

        if ($checkout->shipping_method_id === null) {
            return 0;
        }

        $rate = ShippingRate::query()->find($checkout->shipping_method_id);

        if ($rate === null) {
            return 0;
        }

        return $this->shipping->calculate($rate, $checkout->cart) ?? 0;
    }

    private function taxSettings(Checkout $checkout): TaxSettings
    {
        return TaxSettings::withoutGlobalScopes()->firstOrCreate(
            ['store_id' => $checkout->store_id],
            [
                'mode' => TaxMode::Manual,
                'provider' => 'none',
                'prices_include_tax' => false,
                'config_json' => [
                    'default_rate_basis_points' => 0,
                    'shipping_taxable' => false,
                ],
            ],
        );
    }

    /**
     * @return list<TaxLine>
     */
    private function taxLines(Checkout $checkout, TaxSettings $settings, int $discountedSubtotal, int $shippingAmount): array
    {
        $address = $checkout->shipping_address_json ?? [];

        if ($address === []) {
            return [];
        }

        $taxLines = [];
        $itemTax = $this->taxes->calculate($discountedSubtotal, $settings, $address);

        if ($itemTax->amount > 0) {
            $taxLines[] = new TaxLine('Item tax', $itemTax->rate, $itemTax->amount);
        }

        $shippingTaxable = (bool) (($settings->config_json ?? [])['shipping_taxable'] ?? false);

        if ($shippingTaxable && $shippingAmount > 0) {
            $shippingTax = $this->taxes->calculate($shippingAmount, $settings, $address);

            if ($shippingTax->amount > 0) {
                $taxLines[] = new TaxLine('Shipping tax', $shippingTax->rate, $shippingTax->amount);
            }
        }

        return $taxLines;
    }
}
