<?php

use App\ValueObjects\PricingResult;
use App\ValueObjects\TaxLine;

it('serializes to the expected snapshot shape', function (): void {
    $result = new PricingResult(
        subtotal: 5000,
        discount: 500,
        shipping: 799,
        taxLines: [new TaxLine('VAT', 1900, 850)],
        taxTotal: 850,
        total: 6149,
        currency: 'EUR',
    );

    expect($result->toArray())->toBe([
        'subtotal' => 5000,
        'discount' => 500,
        'shipping' => 799,
        'tax_lines' => [[
            'name' => 'VAT',
            'rate' => 1900,
            'amount' => 850,
        ]],
        'tax' => 850,
        'total' => 6149,
        'currency' => 'EUR',
    ]);
});

it('keeps totals read-only', function (): void {
    $result = new PricingResult(1000, 0, 0, [], 0, 1000, 'USD');

    expect(fn () => $result->subtotal = 500)
        ->toThrow(Error::class);
});
