<?php

use App\Models\CartLine;
use App\Models\Discount;
use App\Services\DiscountService;

it('calculates percent fixed and free shipping discounts', function () {
    $lines = collect([
        (new CartLine(['line_subtotal_amount' => 7500]))->setAttribute('id', 1),
        (new CartLine(['line_subtotal_amount' => 2500]))->setAttribute('id', 2),
    ]);
    $service = new DiscountService;

    $percent = $service->calculate(new Discount(['value_type' => 'percent', 'value_amount' => 10, 'rules_json' => []]), 10000, $lines);
    $fixed = $service->calculate(new Discount(['value_type' => 'fixed', 'value_amount' => 12000, 'rules_json' => []]), 10000, $lines);
    $shipping = $service->calculate(new Discount(['value_type' => 'free_shipping', 'rules_json' => []]), 10000, $lines);

    expect($percent->amount)->toBe(1000)
        ->and($percent->allocations)->toBe([1 => 750, 2 => 250])
        ->and($fixed->amount)->toBe(10000)
        ->and($shipping->freeShipping)->toBeTrue();
});

it('assigns rounding remainder to the final line', function () {
    $lines = collect([
        (new CartLine(['line_subtotal_amount' => 333]))->setAttribute('id', 1),
        (new CartLine(['line_subtotal_amount' => 333]))->setAttribute('id', 2),
        (new CartLine(['line_subtotal_amount' => 334]))->setAttribute('id', 3),
    ]);
    $result = (new DiscountService)->calculate(new Discount(['value_type' => 'percent', 'value_amount' => 10, 'rules_json' => []]), 1000, $lines);

    expect(array_sum($result->allocations))->toBe(100);
});
