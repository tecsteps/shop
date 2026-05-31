<?php

use App\Services\Tax\ManualTaxProvider;
use App\Services\Tax\StripeTaxProvider;
use App\Services\TaxCalculator;

beforeEach(function () {
    $manual = new ManualTaxProvider;
    $this->calculator = new TaxCalculator($manual, new StripeTaxProvider($manual));
});

it('calculates manual tax exclusive', function () {
    expect($this->calculator->addExclusive(10000, 1900))->toBe(1900);
});

it('extracts manual tax from inclusive amount', function () {
    $tax = $this->calculator->extractInclusive(11900, 1900);

    expect($tax)->toBe(1900)
        ->and(11900 - $tax)->toBe(10000);
});

it('returns zero tax when no rate is configured', function () {
    expect($this->calculator->addExclusive(10000, 0))->toBe(0);
});

it('handles zero amount lines', function () {
    expect($this->calculator->addExclusive(0, 1900))->toBe(0);
});

it('calculates tax with non-standard rate', function () {
    expect($this->calculator->addExclusive(8999, 700))->toBe(629);
});

it('extracts tax correctly for small amounts', function () {
    $tax = $this->calculator->extractInclusive(119, 1900);

    expect($tax)->toBe(19)
        ->and(119 - $tax)->toBe(100);
});

it('handles high tax rates', function () {
    expect($this->calculator->addExclusive(10000, 2500))->toBe(2500);
});
