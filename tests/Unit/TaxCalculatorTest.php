<?php

use App\Enums\TaxMode;
use App\Models\TaxSettings;
use App\Services\TaxCalculator;
use App\ValueObjects\TaxLine;

beforeEach(function () {
    $this->calculator = new TaxCalculator;
});

it('adds exclusive tax correctly', function () {
    expect($this->calculator->addExclusive(1000, 1900))->toBe(190);
});

it('extracts inclusive tax correctly', function () {
    // gross=1190, rate=1900 -> net=intdiv(1190*10000, 11900)=1000 -> tax=190
    expect($this->calculator->extractInclusive(1190, 1900))->toBe(190);
});

it('returns zero tax for zero rate', function () {
    $settings = new TaxSettings;
    $settings->mode = TaxMode::Manual;
    $settings->prices_include_tax = false;
    $settings->config_json = ['default_rate_bps' => 0];

    $result = $this->calculator->calculate(1000, $settings);

    expect($result['total'])->toBe(0)
        ->and($result['tax_lines'])->toBeEmpty();
});

it('calculates exclusive tax via settings', function () {
    $settings = new TaxSettings;
    $settings->mode = TaxMode::Manual;
    $settings->prices_include_tax = false;
    $settings->config_json = ['default_rate_bps' => 1900];

    $result = $this->calculator->calculate(1000, $settings);

    expect($result['total'])->toBe(190)
        ->and($result['tax_lines'])->toHaveCount(1)
        ->and($result['tax_lines'][0])->toBeInstanceOf(TaxLine::class)
        ->and($result['tax_lines'][0]->amount)->toBe(190);
});

it('calculates inclusive tax via settings', function () {
    $settings = new TaxSettings;
    $settings->mode = TaxMode::Manual;
    $settings->prices_include_tax = true;
    $settings->config_json = ['default_rate_bps' => 1900];

    $result = $this->calculator->calculate(1190, $settings);

    expect($result['total'])->toBe(190);
});

it('handles 8% tax rate correctly', function () {
    expect($this->calculator->addExclusive(5000, 800))->toBe(400);
});

it('uses integer division for inclusive extraction', function () {
    // Verify deterministic extraction with intdiv
    // gross=1191, rate=1900 -> net=intdiv(1191*10000, 11900)=1000 -> tax=191
    expect($this->calculator->extractInclusive(1191, 1900))->toBe(191);
});
