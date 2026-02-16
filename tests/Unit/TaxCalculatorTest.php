<?php

use App\Services\TaxCalculator;

beforeEach(function () {
    $this->calculator = new TaxCalculator;
});

it('calculates manual tax exclusive', function () {
    $result = $this->calculator->addExclusive(10000, 1900);
    expect($result)->toBe(1900);
});

it('extracts manual tax from inclusive amount', function () {
    $result = $this->calculator->extractInclusive(11900, 1900);
    expect($result)->toBe(1900);
});

it('returns zero tax when no rate is configured', function () {
    $result = $this->calculator->addExclusive(10000, 0);
    expect($result)->toBe(0);
});

it('handles zero amount lines', function () {
    $result = $this->calculator->addExclusive(0, 1900);
    expect($result)->toBe(0);
});

it('calculates tax with non-standard rate', function () {
    $result = $this->calculator->addExclusive(8999, 700);
    expect($result)->toBe(629);
});

it('extracts tax correctly for small amounts', function () {
    $result = $this->calculator->extractInclusive(119, 1900);
    expect($result)->toBe(19);
});

it('handles high tax rates', function () {
    $result = $this->calculator->addExclusive(10000, 2500);
    expect($result)->toBe(2500);
});
