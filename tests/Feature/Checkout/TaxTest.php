<?php

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\ProductStatus;
use App\Enums\ShippingRateType;
use App\Enums\TaxMode;
use App\Enums\VariantStatus;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\TaxSettings;
use App\Services\PricingEngine;

function createTaxTestContext(int $price = 2500, int $quantity = 2): array
{
    $ctx = createStoreContext();
    $store = $ctx['store'];

    $product = Product::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'title' => 'Tax Test',
        'handle' => 'tax-test-'.rand(1000, 9999),
        'status' => ProductStatus::Active,
        'published_at' => now(),
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'price_amount' => $price,
        'currency' => 'EUR',
        'is_default' => true,
        'position' => 0,
        'status' => VariantStatus::Active,
    ]);

    InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 50,
        'quantity_reserved' => 0,
        'policy' => 'deny',
    ]);

    $cart = Cart::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'currency' => 'EUR',
        'cart_version' => 1,
        'status' => CartStatus::Active,
    ]);

    CartLine::create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => $quantity,
        'unit_price_amount' => $price,
        'line_subtotal_amount' => $price * $quantity,
        'line_discount_amount' => 0,
        'line_total_amount' => $price * $quantity,
    ]);

    return array_merge($ctx, compact('product', 'variant', 'cart'));
}

it('calculates exclusive tax correctly at checkout', function () {
    $ctx = createTaxTestContext(2500, 2);

    $zone = ShippingZone::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'name' => 'DE',
        'countries_json' => ['DE'],
        'regions_json' => [],
    ]);

    $rate = ShippingRate::create([
        'zone_id' => $zone->id,
        'name' => 'Standard',
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 499],
        'is_active' => true,
    ]);

    TaxSettings::create([
        'store_id' => $ctx['store']->id,
        'mode' => TaxMode::Manual,
        'provider' => 'none',
        'prices_include_tax' => false,
        'config_json' => ['tax_rate_basis_points' => 1900],
    ]);

    $checkout = Checkout::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $ctx['cart']->id,
        'status' => CheckoutStatus::ShippingSelected,
        'shipping_method_id' => $rate->id,
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    // Tax on discounted subtotal (5000), not shipping
    expect($result->taxTotal)->toBe(950); // round(5000 * 1900 / 10000)
});

it('extracts inclusive tax correctly at checkout', function () {
    $ctx = createTaxTestContext(5950, 2);

    TaxSettings::create([
        'store_id' => $ctx['store']->id,
        'mode' => TaxMode::Manual,
        'provider' => 'none',
        'prices_include_tax' => true,
        'config_json' => ['tax_rate_basis_points' => 1900],
    ]);

    $checkout = Checkout::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $ctx['cart']->id,
        'status' => CheckoutStatus::Started,
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    // gross = 11900, net = intdiv(11900 * 10000, 11900) = 10000, tax = 1900
    expect($result->taxTotal)->toBe(1900);
});

it('applies zero tax when no tax settings exist', function () {
    $ctx = createTaxTestContext(2500, 2);

    $checkout = Checkout::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $ctx['cart']->id,
        'status' => CheckoutStatus::Started,
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    expect($result->taxTotal)->toBe(0);
});

it('stores tax lines in totals_json', function () {
    $ctx = createTaxTestContext(5000, 2);

    TaxSettings::create([
        'store_id' => $ctx['store']->id,
        'mode' => TaxMode::Manual,
        'provider' => 'none',
        'prices_include_tax' => false,
        'config_json' => ['tax_rate_basis_points' => 1900],
    ]);

    $checkout = Checkout::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'cart_id' => $ctx['cart']->id,
        'status' => CheckoutStatus::Started,
    ]);

    $engine = app(PricingEngine::class);
    $result = $engine->calculate($checkout);

    $array = $result->toArray();
    expect($array['tax_lines'])->toHaveCount(1)
        ->and($array['tax_lines'][0]['name'])->toBe('Tax')
        ->and($array['tax_lines'][0]['rate'])->toBe(1900)
        ->and($array['tax_lines'][0]['amount'])->toBe(1900);
});
