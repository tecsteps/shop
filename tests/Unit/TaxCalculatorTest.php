<?php

use App\Services\TaxCalculator;

it('calculates manual tax exclusive', function () {
    $calculator = new TaxCalculator;

    $tax = $calculator->addExclusive(10000, 1900);

    expect($tax)->toBe(1900);
});

it('extracts manual tax from inclusive amount', function () {
    $calculator = new TaxCalculator;

    $tax = $calculator->extractInclusive(11900, 1900);

    expect($tax)->toBe(1900);
});

it('returns zero tax when no rate configured', function () {
    $calculator = new TaxCalculator;

    $tax = $calculator->addExclusive(10000, 0);

    expect($tax)->toBe(0);
});

it('handles zero amount lines', function () {
    $calculator = new TaxCalculator;

    $tax = $calculator->addExclusive(0, 1900);

    expect($tax)->toBe(0);
});

it('calculates tax with non-standard rate', function () {
    $calculator = new TaxCalculator;

    // intdiv(8999 * 700, 10000) = intdiv(6299300, 10000) = 629
    $tax = $calculator->addExclusive(8999, 700);

    expect($tax)->toBe(629);
});

it('extracts tax correctly for small amounts', function () {
    $calculator = new TaxCalculator;

    // net = intdiv(119 * 10000, 11900) = intdiv(1190000, 11900) = 100
    // tax = 119 - 100 = 19
    $tax = $calculator->extractInclusive(119, 1900);

    expect($tax)->toBe(19);
});

it('handles high tax rates', function () {
    $calculator = new TaxCalculator;

    // intdiv(10000 * 2500, 10000) = 2500
    $tax = $calculator->addExclusive(10000, 2500);

    expect($tax)->toBe(2500);
});
