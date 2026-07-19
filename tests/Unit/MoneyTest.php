<?php

use App\Support\Money;

test('formats cents with two decimals and the currency code', function () {
    expect(Money::format(2499, 'EUR'))->toBe('24.99 EUR');
});

test('formats free amounts as 0.00', function () {
    expect(Money::format(0, 'EUR'))->toBe('0.00 EUR');
});

test('adds a comma as thousands separator', function () {
    expect(Money::format(149900, 'EUR'))->toBe('1,499.00 EUR')
        ->and(Money::format(123456789, 'USD'))->toBe('1,234,567.89 USD');
});

test('formats negative amounts with a leading minus sign', function () {
    expect(Money::format(-1250, 'EUR'))->toBe('-12.50 EUR');
});

test('uppercases the currency code', function () {
    expect(Money::format(100, 'usd'))->toBe('1.00 USD');
});
