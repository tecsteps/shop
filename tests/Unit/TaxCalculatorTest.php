<?php

use App\Services\TaxCalculator;

it('rounds each taxable line independently before summing tax', function () {
    $lines = app(TaxCalculator::class)->calculate([3, 3, 3], 0, 1900);

    expect($lines)->toHaveCount(1)
        ->and($lines[0]->amount)->toBe(3);
});

it('extracts included tax by truncating each gross amount', function () {
    $lines = app(TaxCalculator::class)->calculate([1190, 1190], 0, 1900, pricesIncludeTax: true);

    expect($lines)->toHaveCount(1)
        ->and($lines[0]->amount)->toBe(380);
});

it('prefers a country province rate and falls back to a country or default rate', function () {
    $calculator = app(TaxCalculator::class);

    expect($calculator->rateForAddress(['DE' => ['BE' => 700, 'default' => 1900]], 'de', 'be', 500))
        ->toBe(700)
        ->and($calculator->rateForAddress(['DE' => 1900], 'de', null, 500))->toBe(1900)
        ->and($calculator->rateForAddress([], 'US', null, 500))->toBe(500);
});
