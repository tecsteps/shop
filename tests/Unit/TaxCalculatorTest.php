<?php

use App\Models\TaxSettings;
use App\Services\TaxCalculator;

it('calculates exclusive tax using basis points', function () {
    $settings = new TaxSettings(['prices_include_tax' => false, 'config_json' => ['default_rate_bps' => 1900]]);
    $result = (new TaxCalculator)->calculate(10000, $settings);

    expect($result->taxAmount)->toBe(1900)
        ->and($result->grossAmount)->toBe(11900);
});

it('extracts inclusive tax deterministically', function () {
    $calculator = new TaxCalculator;

    expect($calculator->extractInclusive(11900, 1900))->toBe(1900)
        ->and($calculator->extractInclusive(119, 1900))->toBe(19)
        ->and($calculator->addExclusive(8999, 700))->toBe(630);
});

it('returns zero tax for zero rates and amounts', function () {
    $calculator = new TaxCalculator;

    expect($calculator->addExclusive(10000, 0))->toBe(0)
        ->and($calculator->extractInclusive(0, 1900))->toBe(0);
});
