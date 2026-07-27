<?php

use App\Services\TaxCalculator;

uses(Tests\TestCase::class);

function taxCalculator(): TaxCalculator
{
    return app(TaxCalculator::class);
}

test('calculates manual tax exclusive', function () {
    expect(taxCalculator()->addExclusive(10000, 1900))->toBe(1900);
});

test('extracts manual tax from inclusive amount', function () {
    $tax = taxCalculator()->extractInclusive(11900, 1900);

    expect($tax)->toBe(1900)
        ->and(11900 - $tax)->toBe(10000); // net
});

test('returns zero tax when no rate is configured', function () {
    expect(taxCalculator()->addExclusive(10000, 0))->toBe(0);
});

test('handles zero amount lines', function () {
    expect(taxCalculator()->addExclusive(0, 1900))->toBe(0)
        ->and(taxCalculator()->extractInclusive(0, 1900))->toBe(0);
});

test('calculates tax with non-standard rate', function () {
    // 8999 * 700 / 10000 = 629.93 -> 629 (integer truncation, spec 09)
    expect(taxCalculator()->addExclusive(8999, 700))->toBe(629);
});

test('extracts tax correctly for small amounts', function () {
    $tax = taxCalculator()->extractInclusive(119, 1900);

    expect($tax)->toBe(19)
        ->and(119 - $tax)->toBe(100);
});

test('handles high tax rates', function () {
    expect(taxCalculator()->addExclusive(10000, 2500))->toBe(2500);
});
