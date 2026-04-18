<?php

namespace App\Services;

use App\Enums\DiscountValueType;
use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\ShippingRate;
use App\Models\Store;
use App\Models\TaxSettings;
use App\ValueObjects\PricingResult;

class PricingEngine
{
    public function __construct(
        protected DiscountService $discounts,
        protected ShippingCalculator $shipping,
        protected TaxCalculator $tax,
    ) {}

    public function calculate(Checkout $checkout): PricingResult
    {
        $cart = $checkout->cart()->with('lines.variant.product')->first();
        $store = $checkout->store()->first();

        $result = $this->calculateForCart(
            cart: $cart,
            store: $store,
            shippingAddress: $checkout->shipping_address_json ?? [],
            shippingRateId: $checkout->shipping_method_id,
            discountCode: $checkout->discount_code,
        );

        $checkout->totals_json = $result->toArray();
        $checkout->save();

        return $result;
    }

    /**
     * @param  array<string, mixed>  $shippingAddress
     */
    public function calculateForCart(
        Cart $cart,
        Store $store,
        array $shippingAddress = [],
        ?int $shippingRateId = null,
        ?string $discountCode = null,
    ): PricingResult {
        $cart->loadMissing('lines.variant.product');

        $subtotal = (int) $cart->lines->sum('line_subtotal_amount');

        $discountAmount = 0;
        $discount = null;
        $allocations = [];

        if ($discountCode) {
            try {
                $discount = $this->discounts->validate($discountCode, $store, $cart);
                $outcome = $this->discounts->calculate($discount, $cart);
                $discountAmount = (int) $outcome['total'];
                $allocations = $outcome['allocations'];
            } catch (InvalidDiscountException) {
                $discount = null;
                $discountAmount = 0;
                $allocations = [];
            }
        }

        $this->discounts->applyAllocationsToCart($cart, $allocations);
        $cart->loadMissing('lines.variant.product');

        $discountedSubtotal = max(0, $subtotal - $discountAmount);

        $shippingAmount = 0;
        if ($shippingRateId && $this->shipping->requiresShipping($cart)) {
            $rate = ShippingRate::query()->whereKey($shippingRateId)->first();
            if ($rate) {
                $shippingAmount = $this->shipping->calculate($rate, $cart) ?? 0;
            }
        }

        if ($discount && $discount->value_type === DiscountValueType::FreeShipping) {
            $shippingAmount = 0;
        }

        $taxSettings = TaxSettings::query()->where('store_id', $store->id)->first()
            ?? $this->defaultTaxSettings($store);

        $taxableAmount = $discountedSubtotal + $shippingAmount;
        $taxResult = $this->tax->calculate($taxableAmount, $taxSettings, $shippingAddress);

        $taxLines = $taxResult['lines'];
        $taxTotal = $taxResult['total'];

        if ($taxSettings->prices_include_tax) {
            $total = $discountedSubtotal + $shippingAmount;
        } else {
            $total = $discountedSubtotal + $shippingAmount + $taxTotal;
        }

        return new PricingResult(
            subtotal: $subtotal,
            discount: $discountAmount,
            shipping: $shippingAmount,
            taxLines: $taxLines,
            taxTotal: $taxTotal,
            total: $total,
            currency: $cart->currency,
        );
    }

    protected function defaultTaxSettings(Store $store): TaxSettings
    {
        $settings = new TaxSettings;
        $settings->store_id = $store->id;
        $settings->mode = \App\Enums\TaxMode::Manual;
        $settings->provider = 'none';
        $settings->prices_include_tax = false;
        $settings->config_json = ['default_rate_bps' => 0];

        return $settings;
    }
}
