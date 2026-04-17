<?php

use App\Enums\TaxMode;
use App\Models\TaxSettings;
use App\Services\TaxCalculator;
use App\ValueObjects\TaxLine;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->calculator = app(TaxCalculator::class);
});

it('calculates exclusive tax at 19%', function () {
    $settings = TaxSettings::factory()->create([
        'store_id' => $this->store->id,
        'mode' => TaxMode::Manual,
        'prices_include_tax' => false,
        'config_json' => ['default_rate_basis_points' => 1900],
    ]);

    $result = $this->calculator->calculate(1000, $settings, []);

    expect($result['tax_total'])->toBe(190)
        ->and($result['tax_lines'])->toHaveCount(1)
        ->and($result['tax_lines'][0])->toBeInstanceOf(TaxLine::class)
        ->and($result['tax_lines'][0]->rate)->toBe(1900);
});

it('extracts inclusive tax at 19%', function () {
    $settings = TaxSettings::factory()->inclusive()->create([
        'store_id' => $this->store->id,
        'config_json' => ['default_rate_basis_points' => 1900],
    ]);

    $result = $this->calculator->calculate(1190, $settings, []);

    // net = intdiv(1190 * 10000, 11900) = 1000
    // tax = 1190 - 1000 = 190
    expect($result['tax_total'])->toBe(190);
});

it('returns zero tax when rate is zero', function () {
    $settings = TaxSettings::factory()->create([
        'store_id' => $this->store->id,
        'config_json' => ['default_rate_basis_points' => 0],
    ]);

    $result = $this->calculator->calculate(5000, $settings, []);

    expect($result['tax_total'])->toBe(0)
        ->and($result['tax_lines'])->toBeEmpty();
});

it('calculates exclusive tax correctly with addExclusive', function () {
    $tax = $this->calculator->addExclusive(1000, 1900);

    expect($tax)->toBe(190);
});

it('extracts inclusive tax correctly with extractInclusive', function () {
    $tax = $this->calculator->extractInclusive(1190, 1900);

    expect($tax)->toBe(190);
});

it('handles rounding in exclusive tax', function () {
    // 333 * 800 / 10000 = 26.64, rounds to 27
    $tax = $this->calculator->addExclusive(333, 800);

    expect($tax)->toBe(27);
});
