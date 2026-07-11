<?php

use App\ValueObjects\PricingResult;
use App\ValueObjects\TaxLine;

it('serializes a complete deterministic pricing snapshot', function () {
    $result = new PricingResult(10000, 1000, 499, [new TaxLine('Tax', 1900, 1805)], 1805, 11304, 'EUR');

    expect($result->jsonSerialize())->toBe([
        'subtotal' => 10000,
        'discount' => 1000,
        'shipping' => 499,
        'tax_lines' => $result->taxLines,
        'tax_total' => 1805,
        'total' => 11304,
        'currency' => 'EUR',
    ]);
});
