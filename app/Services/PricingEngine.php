<?php

namespace App\Services;

use App\Enums\DiscountValueType;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\ShippingRate;
use App\Models\TaxSettings;
use App\ValueObjects\Address;
use App\ValueObjects\PricingResult;
use Illuminate\Support\Facades\DB;

class PricingEngine
{
    public function __construct(
        protected DiscountService $discountService,
        protected ShippingCalculator $shippingCalculator,
        protected TaxCalculator $taxCalculator
    ) {}

    public function calculate(Checkout $checkout): PricingResult
    {
        $cart = $checkout->cart()->with('lines.variant.product')->first();
        $store = $checkout->store;

        // Step 1 & 2: Line subtotals and cart subtotal
        $subtotal = 0;
        $lines = [];

        foreach ($cart->lines as $line) {
            $lineSubtotal = $line->unit_price_amount * $line->quantity;
            $subtotal += $lineSubtotal;

            $lines[] = [
                'line_id' => $line->id,
                'product_id' => $line->variant?->product_id,
                'collection_ids' => $line->variant?->product_id
                    ? DB::table('collection_products')
                        ->where('product_id', $line->variant->product_id)
                        ->pluck('collection_id')
                        ->toArray()
                    : [],
                'line_subtotal_amount' => $lineSubtotal,
                'quantity' => $line->quantity,
            ];
        }

        // Step 3: Discount
        $discountAmount = 0;
        $lineDiscounts = [];

        if ($checkout->discount_code) {
            $discount = Discount::query()
                ->withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->whereRaw('LOWER(code) = ?', [strtolower($checkout->discount_code)])
                ->first();

            if ($discount) {
                $result = $this->discountService->calculate($discount, $subtotal, $lines);
                $discountAmount = $result['total_discount'];
                $lineDiscounts = $result['line_discounts'];

                // Check for free shipping discount
                if ($discount->value_type === DiscountValueType::FreeShipping) {
                    $freeShipping = true;
                }
            }
        }

        // Apply automatic discounts
        $automaticDiscounts = Discount::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('type', 'automatic')
            ->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->get();

        foreach ($automaticDiscounts as $autoDiscount) {
            $result = $this->discountService->calculate($autoDiscount, $subtotal - $discountAmount, $lines);
            $discountAmount += $result['total_discount'];

            foreach ($result['line_discounts'] as $lineId => $amount) {
                $lineDiscounts[$lineId] = ($lineDiscounts[$lineId] ?? 0) + $amount;
            }

            if ($autoDiscount->value_type === DiscountValueType::FreeShipping) {
                $freeShipping = true;
            }
        }

        // Update cart line discount amounts
        foreach ($lineDiscounts as $lineId => $lineDiscount) {
            $cartLine = $cart->lines->firstWhere('id', $lineId);
            if ($cartLine) {
                $cartLine->update([
                    'line_discount_amount' => $lineDiscount,
                    'line_total_amount' => $cartLine->line_subtotal_amount - $lineDiscount,
                ]);
            }
        }

        // Step 4: Discounted subtotal
        $discountedSubtotal = $subtotal - $discountAmount;

        // Step 5: Shipping
        $shippingAmount = 0;

        if ($checkout->shipping_method_id) {
            $shippingRate = ShippingRate::query()->find($checkout->shipping_method_id);

            if ($shippingRate) {
                $calculated = $this->shippingCalculator->calculate($shippingRate, $cart);
                $shippingAmount = $calculated ?? 0;
            }
        }

        if (isset($freeShipping) && $freeShipping) {
            $shippingAmount = 0;
        }

        // Step 6: Tax
        $taxSettings = TaxSettings::query()
            ->where('store_id', $store->id)
            ->first();

        $taxLines = [];
        $taxTotal = 0;

        if ($taxSettings) {
            $addressData = $checkout->shipping_address_json ?? [];
            $address = Address::fromArray($addressData);

            $taxLineItems = [];
            foreach ($cart->lines as $line) {
                $lineDiscount = $lineDiscounts[$line->id] ?? 0;
                $taxableAmount = $line->line_subtotal_amount - $lineDiscount;
                $taxLineItems[] = ['amount' => $taxableAmount, 'quantity' => $line->quantity];
            }

            $taxResult = $this->taxCalculator->calculate(
                $taxLineItems,
                $shippingAmount,
                $taxSettings,
                $address
            );

            $taxLines = $taxResult->taxLines;
            $taxTotal = $taxResult->totalAmount;
        }

        // Step 7: Total
        $total = $discountedSubtotal + $shippingAmount + $taxTotal;

        $pricingResult = new PricingResult(
            subtotal: $subtotal,
            discount: $discountAmount,
            shipping: $shippingAmount,
            taxLines: $taxLines,
            taxTotal: $taxTotal,
            total: $total,
            currency: $cart->currency,
        );

        // Snapshot totals on checkout
        $checkout->update([
            'totals_json' => $pricingResult->toArray(),
        ]);

        return $pricingResult;
    }
}
