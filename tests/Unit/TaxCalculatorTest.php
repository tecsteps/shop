<?php

use App\Services\TaxCalculator;

it('calculates manual tax exclusive', function () {
    $calc = new TaxCalculator;

    expect($calc->addExclusive(10000, 1900))->toBe(1900);
});

it('extracts manual tax from inclusive amount', function () {
    $calc = new TaxCalculator;

    expect($calc->extractInclusive(11900, 1900))->toBe(1900);
});

it('returns zero tax when no rate is configured', function () {
    $calc = new TaxCalculator;

    expect($calc->addExclusive(10000, 0))->toBe(0);
});

it('handles zero amount lines', function () {
    $calc = new TaxCalculator;

    expect($calc->addExclusive(0, 1900))->toBe(0);
});

it('calculates tax with non-standard rate', function () {
    $calc = new TaxCalculator;

    expect($calc->addExclusive(8999, 700))->toBe(629);
});

it('extracts tax correctly for small amounts', function () {
    $calc = new TaxCalculator;

    expect($calc->extractInclusive(119, 1900))->toBe(19);
});

it('handles high tax rates', function () {
    $calc = new TaxCalculator;

    expect($calc->addExclusive(10000, 2500))->toBe(2500);
});
