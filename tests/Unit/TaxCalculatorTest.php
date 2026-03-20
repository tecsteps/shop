<?php

use App\Models\TaxSettings;
use App\Services\TaxCalculator;

beforeEach(function () {
    $this->calculator = new TaxCalculator;
});

it('calculates exclusive tax correctly', function () {
    $settings = new TaxSettings([
        'mode' => 'manual',
        'rate' => 1900,
        'prices_include_tax' => false,
        'tax_name' => 'VAT',
        'is_active' => true,
    ]);

    $result = $this->calculator->calculate(5499, $settings);

    expect($result->taxAmount)->toBe(1045)
        ->and($result->taxLines)->toHaveCount(1)
        ->and($result->taxLines[0]->name)->toBe('VAT')
        ->and($result->taxLines[0]->rate)->toBe(1900);
});

it('adds exclusive tax to net amount', function () {
    $tax = $this->calculator->addExclusive(1000, 1900);
    expect($tax)->toBe(190);
});

it('extracts inclusive tax correctly', function () {
    $tax = $this->calculator->extractInclusive(11900, 1900);
    expect($tax)->toBe(1900);
});

it('handles inclusive tax with integer division', function () {
    $tax = $this->calculator->extractInclusive(1190, 1900);
    expect($tax)->toBe(190);
});

it('returns zero tax when settings are null', function () {
    $result = $this->calculator->calculate(5000, null);

    expect($result->taxAmount)->toBe(0)
        ->and($result->taxLines)->toBeEmpty();
});

it('returns zero tax when rate is zero', function () {
    $settings = new TaxSettings([
        'mode' => 'manual',
        'rate' => 0,
        'prices_include_tax' => false,
        'tax_name' => 'VAT',
        'is_active' => true,
    ]);

    $result = $this->calculator->calculate(5000, $settings);
    expect($result->taxAmount)->toBe(0);
});

it('calculates tax on discounted amounts', function () {
    $settings = new TaxSettings([
        'mode' => 'manual',
        'rate' => 1900,
        'prices_include_tax' => false,
        'tax_name' => 'VAT',
        'is_active' => true,
    ]);

    // Subtotal 10000, discount 1000, shipping 500 => taxable = 9500
    $taxableAmount = 9500;
    $result = $this->calculator->calculate($taxableAmount, $settings);

    expect($result->taxAmount)->toBe(1805);
});
