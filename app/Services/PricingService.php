<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Discount;
use App\Models\ShippingRate;

class PricingService
{
    public function __construct(
        private readonly ShippingService $shippingService,
        private readonly TaxService $taxService,
    ) {}

    /**
     * @param  array<string, mixed>|null  $address
     * @return array<string, mixed>
     */
    public function calculate(
        Cart $cart,
        ?array $address = null,
        ?ShippingRate $shippingRate = null,
        ?Discount $discount = null,
    ): array {
        $cart->loadMissing('store', 'lines.variant');

        $subtotal = (int) $cart->lines->sum('line_subtotal_amount');
        $discountAmount = (int) $cart->lines->sum('line_discount_amount');
        $discountedSubtotal = max(0, $subtotal - $discountAmount);

        $shippingAmount = $this->shippingService->calculateShippingAmount($cart, $shippingRate, $discount, $address);

        $tax = $this->taxService->calculate(
            $cart->store,
            $cart,
            $shippingAmount,
            $address,
        );

        $total = $tax['prices_include_tax']
            ? $discountedSubtotal + $shippingAmount
            : $discountedSubtotal + $shippingAmount + $tax['tax_total'];

        return [
            'subtotal' => $subtotal,
            'discount' => $discountAmount,
            'shipping' => $shippingAmount,
            'tax' => $tax['tax_total'],
            'total' => $total,
            'currency' => $cart->currency,
            'tax_lines' => $tax['tax_lines'],
            'line_taxes' => $tax['line_taxes'],
            'shipping_tax' => $tax['shipping_tax'],
            'prices_include_tax' => $tax['prices_include_tax'],
            'tax_provider_snapshot' => $tax['provider_snapshot'],
        ];
    }
}
