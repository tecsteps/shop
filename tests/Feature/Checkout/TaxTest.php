<?php

use App\Enums\CheckoutStatus;
use App\Enums\VariantStatus;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\TaxSettings;
use App\Services\PricingEngine;

function createTaxTestContext(): array
{
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $product = Product::factory()->for($store)->active()->create();
    $variant = ProductVariant::factory()->for($product)->create([
        'price_amount' => 10000,
        'status' => VariantStatus::Active,
    ]);

    $cart = Cart::factory()->for($store)->create();
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 10000,
        'line_subtotal_amount' => 10000,
        'line_total_amount' => 10000,
    ]);

    $checkout = Checkout::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::Started,
    ]);

    return array_merge($ctx, [
        'product' => $product,
        'variant' => $variant,
        'cart' => $cart,
        'checkout' => $checkout,
    ]);
}

it('calculates exclusive tax correctly at checkout', function () {
    $ctx = createTaxTestContext();
    $store = $ctx['store'];
    $checkout = $ctx['checkout'];

    TaxSettings::factory()->create([
        'store_id' => $store->id,
        'prices_include_tax' => false,
        'config_json' => ['default_rate' => 1900],
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    expect($result->taxTotal)->toBe(1900)
        ->and($result->total)->toBe(11900);
});

it('extracts inclusive tax correctly at checkout', function () {
    $ctx = createTaxTestContext();
    $store = $ctx['store'];
    $checkout = $ctx['checkout'];

    TaxSettings::factory()->create([
        'store_id' => $store->id,
        'prices_include_tax' => true,
        'config_json' => ['default_rate' => 1900],
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    // net = intdiv(10000 * 10000, 11900) = intdiv(100000000, 11900) = 8403
    // tax = 10000 - 8403 = 1597
    // total for inclusive: discounted_subtotal + shipping = 10000 + 0 = 10000
    expect($result->taxTotal)->toBe(1597)
        ->and($result->total)->toBe(10000);
});

it('applies zero tax when no tax settings exist', function () {
    $ctx = createTaxTestContext();
    $checkout = $ctx['checkout'];

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    expect($result->taxTotal)->toBe(0)
        ->and($result->total)->toBe(10000);
});

it('stores tax lines in totals_json', function () {
    $ctx = createTaxTestContext();
    $store = $ctx['store'];
    $checkout = $ctx['checkout'];

    TaxSettings::factory()->create([
        'store_id' => $store->id,
        'prices_include_tax' => false,
        'config_json' => ['default_rate' => 1000],
    ]);

    $engine = app(PricingEngine::class);
    $engine->calculate($checkout);

    $checkout->refresh();
    $totals = $checkout->totals_json;

    expect($totals)->toHaveKey('tax_lines')
        ->and($totals['tax_lines'])->toHaveCount(1)
        ->and($totals['tax_lines'][0]['name'])->toBe('Tax')
        ->and($totals['tax_lines'][0]['rate'])->toBe(1000)
        ->and($totals['tax_lines'][0]['amount'])->toBe(1000);
});
