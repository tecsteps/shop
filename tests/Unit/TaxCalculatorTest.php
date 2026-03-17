<?php

use App\Services\TaxCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('calculates manual tax exclusive', function () {
    $taxCalc = new TaxCalculator;

    $result = $taxCalc->addExclusive(10000, 1900);

    expect($result)->toBe(1900);
});

it('extracts manual tax from inclusive amount', function () {
    $taxCalc = new TaxCalculator;

    $taxAmount = $taxCalc->extractInclusive(11900, 1900);
    $netAmount = 11900 - $taxAmount;

    expect($taxAmount)->toBe(1900)
        ->and($netAmount)->toBe(10000);
});

it('returns zero tax when no rate is configured', function () {
    $taxCalc = new TaxCalculator;

    expect($taxCalc->addExclusive(10000, 0))->toBe(0)
        ->and($taxCalc->extractInclusive(10000, 0))->toBe(0);
});

it('handles zero amount lines', function () {
    $taxCalc = new TaxCalculator;

    expect($taxCalc->addExclusive(0, 1900))->toBe(0);
});

it('calculates tax with non-standard rate', function () {
    $taxCalc = new TaxCalculator;

    // 7% of 8999 = 629.93 -> 630 rounded
    $result = $taxCalc->addExclusive(8999, 700);

    expect($result)->toBe(630);
});

it('extracts tax correctly for small amounts', function () {
    $taxCalc = new TaxCalculator;

    $result = $taxCalc->extractInclusive(119, 1900);

    expect($result)->toBe(19);
});

it('handles high tax rates', function () {
    $taxCalc = new TaxCalculator;

    $result = $taxCalc->addExclusive(10000, 2500);

    expect($result)->toBe(2500);
});
