<?php

use App\Enums\TaxMode;
use App\Models\TaxSettings;
use App\Services\TaxCalculator;

it('returns zero tax when rate is zero', function (): void {
    $settings = new TaxSettings;
    $settings->store_id = 1;
    $settings->mode = TaxMode::Manual;
    $settings->provider = 'none';
    $settings->prices_include_tax = false;
    $settings->config_json = ['default_rate_bps' => 0];

    $result = app(TaxCalculator::class)->calculate(5000, $settings);

    expect($result['total'])->toBe(0);
    expect($result['lines'])->toBeEmpty();
});

it('calculates tax-exclusive amounts using basis points', function (): void {
    $calc = app(TaxCalculator::class);

    expect($calc->addExclusive(1000, 1900))->toBe(190);
    expect($calc->addExclusive(2500, 800))->toBe(200);
});

it('extracts tax-inclusive amounts via integer division', function (): void {
    $calc = app(TaxCalculator::class);

    expect($calc->extractInclusive(1190, 1900))->toBe(190);
    expect($calc->extractInclusive(0, 1900))->toBe(0);
    expect($calc->extractInclusive(1000, 0))->toBe(0);
});

it('calculates with region override when set', function (): void {
    $settings = new TaxSettings;
    $settings->store_id = 1;
    $settings->mode = TaxMode::Manual;
    $settings->provider = 'none';
    $settings->prices_include_tax = false;
    $settings->config_json = [
        'default_rate_bps' => 1000,
        'region_rates' => ['US-CA' => 800],
    ];

    $result = app(TaxCalculator::class)->calculate(1000, $settings, ['country_code' => 'US', 'province_code' => 'US-CA']);

    expect($result['total'])->toBe(80);
    expect($result['lines'][0]->rate)->toBe(800);
});

it('names tax lines using config label', function (): void {
    $settings = new TaxSettings;
    $settings->store_id = 1;
    $settings->mode = TaxMode::Manual;
    $settings->provider = 'none';
    $settings->prices_include_tax = false;
    $settings->config_json = ['default_rate_bps' => 1900, 'rate_name' => 'VAT'];

    $result = app(TaxCalculator::class)->calculate(1000, $settings);

    expect($result['lines'][0]->name)->toBe('VAT');
});

it('handles tax-inclusive pricing correctly', function (): void {
    $settings = new TaxSettings;
    $settings->store_id = 1;
    $settings->mode = TaxMode::Manual;
    $settings->provider = 'none';
    $settings->prices_include_tax = true;
    $settings->config_json = ['default_rate_bps' => 1900];

    $result = app(TaxCalculator::class)->calculate(1190, $settings);

    expect($result['total'])->toBe(190);
});
