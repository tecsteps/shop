<?php

use App\Enums\DiscountValueType;
use App\Enums\ShippingRateType;
use App\Enums\TaxMode;
use App\Models\Discount;
use App\Models\TaxSettings;
use App\Services\PricingEngine;
use App\ValueObjects\Address;
use App\ValueObjects\PricingResult;
use App\ValueObjects\ShippingRateVO;

uses(Tests\TestCase::class);

/**
 * Build a pricing line for the engine.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function pricingLine(int $unitPrice, int $quantity, array $overrides = []): array
{
    return array_merge([
        'variant_id' => 1,
        'product_id' => 1,
        'collection_ids' => [],
        'quantity' => $quantity,
        'unit_price_amount' => $unitPrice,
        'requires_shipping' => true,
    ], $overrides);
}

function pricingTaxSettings(bool $inclusive = false, int $rateBps = 1900): TaxSettings
{
    return new TaxSettings([
        'mode' => TaxMode::Manual,
        'prices_include_tax' => $inclusive,
        'config_json' => ['default_rate_bps' => $rateBps],
    ]);
}

function pricingAddress(): Address
{
    return Address::fromArray(['country_code' => 'DE']);
}

function pricingEngine(): PricingEngine
{
    return app(PricingEngine::class);
}

function percentDiscount(int $value): Discount
{
    return new Discount(['value_type' => DiscountValueType::Percent, 'value_amount' => $value, 'rules_json' => []]);
}

function fixedDiscount(int $amount): Discount
{
    return new Discount(['value_type' => DiscountValueType::Fixed, 'value_amount' => $amount, 'rules_json' => []]);
}

function flatShipping(int $amount): ShippingRateVO
{
    return new ShippingRateVO(id: 1, name: 'Flat', amount: $amount, type: ShippingRateType::Flat);
}

test('calculates subtotal from line items', function () {
    $result = pricingEngine()->calculate(
        [pricingLine(2499, 2), pricingLine(7999, 1)],
        null, [], null, pricingTaxSettings(), null, 'USD',
    );

    expect($result->subtotal)->toBe(12997);
});

test('calculates subtotal for a single line', function () {
    $result = pricingEngine()->calculate(
        [pricingLine(1500, 3)],
        null, [], null, pricingTaxSettings(), null, 'USD',
    );

    expect($result->subtotal)->toBe(4500);
});

test('returns zero subtotal for empty cart', function () {
    $result = pricingEngine()->calculate(
        [],
        null, [], null, pricingTaxSettings(), null, 'USD',
    );

    expect($result->subtotal)->toBe(0)
        ->and($result->total)->toBe(0);
});

test('applies percent discount correctly', function () {
    $result = pricingEngine()->calculate(
        [pricingLine(10000, 1)],
        percentDiscount(10), [], null, pricingTaxSettings(), null, 'USD',
    );

    expect($result->discount)->toBe(1000)
        ->and($result->subtotal - $result->discount)->toBe(9000);
});

test('applies fixed discount correctly', function () {
    $result = pricingEngine()->calculate(
        [pricingLine(10000, 1)],
        fixedDiscount(500), [], null, pricingTaxSettings(), null, 'USD',
    );

    expect($result->discount)->toBe(500)
        ->and($result->subtotal - $result->discount)->toBe(9500);
});

test('caps fixed discount at subtotal so it never goes negative', function () {
    $result = pricingEngine()->calculate(
        [pricingLine(300, 1)],
        fixedDiscount(500), [], null, pricingTaxSettings(), null, 'USD',
    );

    expect($result->discount)->toBe(300)
        ->and($result->subtotal - $result->discount)->toBe(0);
});

test('applies free shipping discount by zeroing shipping', function () {
    $freeShipping = new Discount(['value_type' => DiscountValueType::FreeShipping, 'value_amount' => 0, 'rules_json' => []]);

    $result = pricingEngine()->calculate(
        [pricingLine(5000, 1)],
        $freeShipping, [], flatShipping(499), pricingTaxSettings(), null, 'USD',
    );

    expect($result->shipping)->toBe(0);
});

test('calculates tax exclusive correctly', function () {
    $result = pricingEngine()->calculate(
        [pricingLine(10000, 1)],
        null, [], null, pricingTaxSettings(inclusive: false, rateBps: 1900), pricingAddress(), 'USD',
    );

    expect($result->taxTotal)->toBe(1900)
        ->and($result->total)->toBe(11900);
});

test('extracts tax from inclusive price correctly', function () {
    $result = pricingEngine()->calculate(
        [pricingLine(11900, 1)],
        null, [], null, pricingTaxSettings(inclusive: true, rateBps: 1900), pricingAddress(), 'USD',
    );

    expect($result->taxTotal)->toBe(1900)
        ->and($result->subtotal - $result->taxTotal)->toBe(1000 * 10) // net = 10000
        ->and($result->total)->toBe(11900);
});

test('returns zero tax when rate is zero', function () {
    $result = pricingEngine()->calculate(
        [pricingLine(10000, 1)],
        null, [], null, pricingTaxSettings(rateBps: 0), pricingAddress(), 'USD',
    );

    expect($result->taxTotal)->toBe(0)
        ->and($result->taxLines)->toBeEmpty();
});

test('calculates shipping flat rate', function () {
    $result = pricingEngine()->calculate(
        [pricingLine(5000, 1)],
        null, [], flatShipping(499), pricingTaxSettings(), null, 'USD',
    );

    expect($result->shipping)->toBe(499);
});

test('calculates full checkout totals end to end', function () {
    // 2 lines x 2499 = 4998, 10% code, flat shipping 499, 19% tax inclusive.
    $result = pricingEngine()->calculate(
        [pricingLine(2499, 1), pricingLine(2499, 1)],
        percentDiscount(10), [], flatShipping(499), pricingTaxSettings(inclusive: true, rateBps: 1900), pricingAddress(), 'USD',
    );

    expect($result->subtotal)->toBe(4998)
        ->and($result->discount)->toBe(499)
        ->and($result->shipping)->toBe(499)
        ->and($result->taxTotal)->toBe(800) // extracted from post-discount gross
        ->and($result->total)->toBe(4998);
});

test('handles rounding correctly with odd cent amounts', function () {
    $result = pricingEngine()->calculate(
        [pricingLine(3333, 1), pricingLine(3333, 1), pricingLine(3334, 1)],
        percentDiscount(10), [], null, pricingTaxSettings(), null, 'USD',
    );

    expect($result->discount)->toBe(1000)
        ->and(array_sum($result->lineDiscounts))->toBe(1000);
});

test('produces identical results for identical inputs', function () {
    $run = fn (): PricingResult => pricingEngine()->calculate(
        [pricingLine(2499, 2), pricingLine(7999, 1)],
        percentDiscount(15), [], flatShipping(899), pricingTaxSettings(inclusive: false, rateBps: 700), pricingAddress(), 'USD',
    );

    expect($run()->toArray())->toBe($run()->toArray());
});
