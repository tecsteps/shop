<?php

use App\Services\TaxCalculator;

beforeEach(function () {
    $this->taxCalculator = new TaxCalculator;
});

it('calculates manual tax exclusive', function () {
    $tax = $this->taxCalculator->addExclusive(10000, 1900);

    expect($tax)->toBe(1900);
});

it('extracts manual tax from inclusive amount', function () {
    $tax = $this->taxCalculator->extractInclusive(11900, 1900);

    expect($tax)->toBe(1900);
});

it('returns zero tax when no rate is configured', function () {
    $tax = $this->taxCalculator->addExclusive(10000, 0);

    expect($tax)->toBe(0);
});

it('handles zero amount lines', function () {
    $tax = $this->taxCalculator->addExclusive(0, 1900);

    expect($tax)->toBe(0);
});

it('calculates tax with non-standard rate', function () {
    $tax = $this->taxCalculator->addExclusive(8999, 700);

    // 8999 * 700 / 10000 = 629.93 -> rounds to 630
    expect($tax)->toBe(630);
});

it('extracts tax correctly for small amounts', function () {
    $tax = $this->taxCalculator->extractInclusive(119, 1900);

    // net = round(119 * 10000 / 11900) = round(100) = 100
    // tax = 119 - 100 = 19
    expect($tax)->toBe(19);
});

it('handles high tax rates', function () {
    $tax = $this->taxCalculator->addExclusive(10000, 2500);

    expect($tax)->toBe(2500);
});
