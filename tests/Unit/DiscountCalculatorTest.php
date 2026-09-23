<?php

use App\Models\Discount;
use App\Services\DiscountCalculator;

it('allocates a percentage discount proportionally with the remaining cent assigned to a line', function () {
    $discount = new Discount([
        'type' => 'percentage',
        'value' => 10,
        'is_active' => true,
        'rules_json' => [],
    ]);
    $lines = [
        ['variant_id' => 1, 'line_subtotal_amount' => 1001],
        ['variant_id' => 2, 'line_subtotal_amount' => 1000],
    ];

    $result = app(DiscountCalculator::class)->calculate($discount, $lines, 2001);

    expect($result)->toBe(['amount' => 200, 'allocations' => [1 => 100, 2 => 100]]);
});

it('applies product eligibility only to matching line items', function () {
    $discount = new Discount([
        'type' => 'fixed_amount',
        'value' => 300,
        'is_active' => true,
        'rules_json' => ['product_ids' => [12]],
    ]);
    $lines = [
        ['variant_id' => 1, 'product_id' => 12, 'line_subtotal_amount' => 500],
        ['variant_id' => 2, 'product_id' => 13, 'line_subtotal_amount' => 500],
    ];

    $result = app(DiscountCalculator::class)->calculate($discount, $lines, 1000);

    expect($result)->toBe(['amount' => 300, 'allocations' => [1 => 300]]);
});
