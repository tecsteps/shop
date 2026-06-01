<?php

use App\Support\Storefront\PriceFormatter;

it('formats whole and fractional amounts with the currency code after', function () {
    expect(PriceFormatter::format(2499, 'EUR'))->toBe('24.99 EUR');
});

it('formats free amounts as 0.00', function () {
    expect(PriceFormatter::format(0, 'EUR'))->toBe('0.00 EUR');
});

it('adds thousands separators', function () {
    expect(PriceFormatter::format(149900, 'EUR'))->toBe('1,499.00 EUR');
});

it('prefixes negative (refund) amounts with a minus sign', function () {
    expect(PriceFormatter::format(-1250, 'EUR'))->toBe('-12.50 EUR');
});

it('pads single-digit minor units', function () {
    expect(PriceFormatter::format(105, 'USD'))->toBe('1.05 USD');
});

it('uppercases the currency code', function () {
    expect(PriceFormatter::format(500, 'usd'))->toBe('5.00 USD');
});
