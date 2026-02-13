<?php

use App\Models\Store;
use App\Models\TaxSettings;
use App\Services\TaxCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    $this->calculator = app(TaxCalculator::class);
});

test('calculates manual tax exclusive', function () {
    $settings = TaxSettings::factory()->create([
        'store_id' => $this->store->id,
        'prices_include_tax' => false,
        'config_json' => ['default_rate' => 1900],
    ]);

    $result = $this->calculator->calculate(10000, $settings);

    expect($result['tax_total'])->toBe(1900);
    expect($result['tax_lines'])->toHaveCount(1);
    expect($result['tax_lines'][0]->rate)->toBe(1900);
    expect($result['tax_lines'][0]->amount)->toBe(1900);
});

test('extracts manual tax from inclusive amount', function () {
    $settings = TaxSettings::factory()->inclusive()->create([
        'store_id' => $this->store->id,
        'config_json' => ['default_rate' => 1900],
    ]);

    $result = $this->calculator->calculate(11900, $settings);

    expect($result['tax_total'])->toBe(1900);
});

test('returns zero tax when no rate is configured', function () {
    $settings = TaxSettings::factory()->zeroRate()->create([
        'store_id' => $this->store->id,
    ]);

    $result = $this->calculator->calculate(10000, $settings);

    expect($result['tax_total'])->toBe(0);
    expect($result['tax_lines'])->toBeEmpty();
});

test('handles zero amount lines', function () {
    $settings = TaxSettings::factory()->create([
        'store_id' => $this->store->id,
        'config_json' => ['default_rate' => 1900],
    ]);

    $result = $this->calculator->calculate(0, $settings);

    expect($result['tax_total'])->toBe(0);
});

test('calculates tax with non-standard rate', function () {
    $settings = TaxSettings::factory()->create([
        'store_id' => $this->store->id,
        'prices_include_tax' => false,
        'config_json' => ['default_rate' => 700],
    ]);

    $result = $this->calculator->calculate(8999, $settings);

    // 8999 * 700 / 10000 = 629.93 => rounds to 630
    expect($result['tax_total'])->toBe(630);
});

test('extracts tax correctly for small amounts', function () {
    $settings = TaxSettings::factory()->inclusive()->create([
        'store_id' => $this->store->id,
        'config_json' => ['default_rate' => 1900],
    ]);

    $result = $this->calculator->calculate(119, $settings);

    // net = intdiv(119 * 10000, 11900) = intdiv(1190000, 11900) = 100
    // tax = 119 - 100 = 19
    expect($result['tax_total'])->toBe(19);
});

test('handles high tax rates', function () {
    $settings = TaxSettings::factory()->create([
        'store_id' => $this->store->id,
        'prices_include_tax' => false,
        'config_json' => ['default_rate' => 2500],
    ]);

    $result = $this->calculator->calculate(10000, $settings);

    expect($result['tax_total'])->toBe(2500);
});

test('returns zero tax when no settings exist', function () {
    $result = $this->calculator->calculate(10000, null);

    expect($result['tax_total'])->toBe(0);
    expect($result['tax_lines'])->toBeEmpty();
});

test('extractInclusive calculates correctly', function () {
    // Price 1190 cents, rate 1900 (19%)
    // net = intdiv(1190 * 10000, 11900) = intdiv(11900000, 11900) = 1000
    // tax = 1190 - 1000 = 190
    expect($this->calculator->extractInclusive(1190, 1900))->toBe(190);
});

test('addExclusive calculates correctly', function () {
    // 1000 * 1900 / 10000 = 190
    expect($this->calculator->addExclusive(1000, 1900))->toBe(190);
});
