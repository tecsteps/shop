<?php

use App\Models\Discount;
use App\Services\PricingEngine;

it('calculates line discounts, taxable shipping, and a store currency total in minor units', function () {
    $discount = new Discount([
        'type' => 'percentage',
        'value' => 10,
        'is_active' => true,
        'rules_json' => [],
    ]);

    $result = app(PricingEngine::class)->calculate(
        lines: [['variant_id' => 9, 'quantity' => 2, 'unit_price_amount' => 1000]],
        shippingAmount: 500,
        discount: $discount,
        taxRate: 1900,
        shippingTaxable: true,
        currency: 'EUR',
    );

    expect($result->subtotal)->toBe(2000)
        ->and($result->discount)->toBe(200)
        ->and($result->shipping)->toBe(500)
        ->and($result->taxTotal)->toBe(437)
        ->and($result->total)->toBe(2737)
        ->and($result->currency)->toBe('EUR')
        ->and($result->discountAllocations)->toBe([9 => 200]);
});

it('does not add included tax on top of the customer total', function () {
    $result = app(PricingEngine::class)->calculate(
        lines: [['variant_id' => 3, 'quantity' => 1, 'unit_price_amount' => 1190]],
        taxRate: 1900,
        pricesIncludeTax: true,
    );

    expect($result->taxTotal)->toBe(190)
        ->and($result->total)->toBe(1190);
});
