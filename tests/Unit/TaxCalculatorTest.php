<?php

use App\Services\TaxCalculator;

it('calculates manual tax exclusive', function () {
    $tax = new TaxCalculator;

    expect($tax->addExclusive(10000, 1900))->toBe(1900);
});

it('extracts manual tax from inclusive amount', function () {
    $tax = new TaxCalculator;

    $extracted = $tax->extractInclusive(11900, 1900);

    expect($extracted)->toBe(1900);
    expect(11900 - $extracted)->toBe(10000);
});

it('returns zero tax when no rate is configured', function () {
    $tax = new TaxCalculator;

    expect($tax->addExclusive(10000, 0))->toBe(0);
    expect($tax->extractInclusive(10000, 0))->toBe(0);
});

it('handles zero amount lines', function () {
    $tax = new TaxCalculator;

    expect($tax->addExclusive(0, 1900))->toBe(0);
    expect($tax->extractInclusive(0, 1900))->toBe(0);
});

it('calculates tax with non-standard rate', function () {
    $tax = new TaxCalculator;

    expect($tax->addExclusive(8999, 700))->toBe(629);
});

it('extracts tax correctly for small amounts', function () {
    $tax = new TaxCalculator;

    $extracted = $tax->extractInclusive(119, 1900);

    expect($extracted)->toBe(19);
    expect(119 - $extracted)->toBe(100);
});

it('handles high tax rates', function () {
    $tax = new TaxCalculator;

    expect($tax->addExclusive(10000, 2500))->toBe(2500);
});
