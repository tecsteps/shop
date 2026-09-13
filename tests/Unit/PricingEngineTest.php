<?php

use App\Services\DiscountService;
use App\Services\PricingEngine;
use App\Services\ShippingCalculator;
use App\Services\Tax\ManualTaxProvider;
use App\Services\Tax\StripeTaxProvider;
use App\Services\TaxCalculator;

function pricingEngine(): PricingEngine
{
    return new PricingEngine(
        new DiscountService,
        new ShippingCalculator,
        new TaxCalculator(new ManualTaxProvider, new StripeTaxProvider)
    );
}

it('calculates subtotal from line items', function () {
    $engine = pricingEngine();

    $a = $engine->lineSubtotal(2499, 2);
    $b = $engine->lineSubtotal(7999, 1);

    expect($engine->sumSubtotals([$a, $b]))->toBe(12997);
});

it('calculates subtotal for a single line', function () {
    expect(pricingEngine()->lineSubtotal(1500, 3))->toBe(4500);
});

it('returns zero subtotal for empty cart', function () {
    expect(pricingEngine()->sumSubtotals([]))->toBe(0);
});

it('applies percent discount correctly', function () {
    $engine = pricingEngine();

    $discount = $engine->percentDiscount(10000, 10);

    expect($discount)->toBe(1000);
    expect($engine->discountedSubtotal(10000, $discount))->toBe(9000);
});

it('applies fixed discount correctly', function () {
    $engine = pricingEngine();

    $discount = $engine->fixedDiscount(10000, 500);

    expect($discount)->toBe(500);
    expect($engine->discountedSubtotal(10000, $discount))->toBe(9500);
});

it('caps fixed discount at subtotal so it never goes negative', function () {
    $engine = pricingEngine();

    $discount = $engine->fixedDiscount(300, 500);

    expect($discount)->toBe(300);
    expect($engine->discountedSubtotal(300, $discount))->toBe(0);
});

it('applies free shipping discount by zeroing shipping', function () {
    $service = new DiscountService;
    $discount = new App\Models\Discount([
        'value_type' => App\Enums\DiscountValueType::FreeShipping,
        'value_amount' => 0,
        'rules_json' => [],
    ]);

    $result = $service->calculate($discount, 5000, [
        ['line_id' => 1, 'subtotal' => 5000],
    ]);

    expect($result['free_shipping'])->toBeTrue();
    expect($result['total'])->toBe(0);
});

it('calculates tax exclusive correctly', function () {
    $tax = new TaxCalculator(new ManualTaxProvider, new StripeTaxProvider);

    expect($tax->addExclusive(10000, 1900))->toBe(1900);
    expect(10000 + $tax->addExclusive(10000, 1900))->toBe(11900);
});

it('extracts tax from inclusive price correctly', function () {
    $tax = new TaxCalculator(new ManualTaxProvider, new StripeTaxProvider);

    $extracted = $tax->extractInclusive(11900, 1900);

    expect($extracted)->toBe(1900);
    expect(11900 - $extracted)->toBe(10000);
});

it('returns zero tax when rate is zero', function () {
    $tax = new TaxCalculator(new ManualTaxProvider, new StripeTaxProvider);

    expect($tax->addExclusive(10000, 0))->toBe(0);
    expect($tax->extractInclusive(11900, 0))->toBe(0);
});

it('calculates shipping flat rate', function () {
    $calculator = new ShippingCalculator;
    $rate = new App\Models\ShippingRate([
        'type' => App\Enums\ShippingRateType::Flat,
        'config_json' => ['amount' => 499],
    ]);
    $cart = new App\Models\Cart;

    expect($calculator->calculate($rate, $cart))->toBe(499);
});

it('calculates full checkout totals end to end', function () {
    $engine = pricingEngine();

    $subtotal = $engine->lineSubtotal(2499, 2);
    expect($subtotal)->toBe(4998);

    $discount = $engine->percentDiscount($subtotal, 10);
    expect($discount)->toBe(499);

    $discounted = $engine->discountedSubtotal($subtotal, $discount);
    expect($discounted)->toBe(4499);

    $shipping = 499;
    $gross = $discounted + $shipping;

    $tax = new TaxCalculator(new ManualTaxProvider, new StripeTaxProvider);
    $extracted = $tax->extractInclusive($gross, 1900);

    expect($engine->total($discounted, $shipping, $extracted, true))->toBe(4998);
    expect($extracted)->toBe(798);
});

it('handles rounding correctly with odd cent amounts', function () {
    $service = new DiscountService;

    $allocations = $service->allocate(100, [
        ['line_id' => 1, 'subtotal' => 3333],
        ['line_id' => 2, 'subtotal' => 3333],
        ['line_id' => 3, 'subtotal' => 3334],
    ]);

    expect(array_sum($allocations))->toBe(100);
});

it('produces identical results for identical inputs', function () {
    $engine = pricingEngine();

    $first = [$engine->percentDiscount(4998, 10), $engine->fixedDiscount(4998, 499)];
    $second = [$engine->percentDiscount(4998, 10), $engine->fixedDiscount(4998, 499)];

    expect($first)->toBe($second);
});
