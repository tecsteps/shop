<?php

use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Services\ShippingCalculator;

it('matches a zone by country code', function () {
    $store = Store::factory()->create();
    $zone = ShippingZone::factory()->create(['store_id' => $store->id, 'countries_json' => ['DE', 'AT', 'CH'], 'regions_json' => []]);

    expect(app(ShippingCalculator::class)->getMatchingZone($store, ['country_code' => 'DE'])->id)->toBe($zone->id);
});

it('matches a zone by region code', function () {
    $store = Store::factory()->create();
    $zone = ShippingZone::factory()->create(['store_id' => $store->id, 'countries_json' => ['US'], 'regions_json' => ['US-NY', 'US-CA']]);

    expect(app(ShippingCalculator::class)->getMatchingZone($store, ['country_code' => 'US', 'province_code' => 'US-NY'])->id)->toBe($zone->id);
});

it('returns empty when no zone matches the address', function () {
    $store = Store::factory()->create();
    ShippingZone::factory()->create(['store_id' => $store->id, 'countries_json' => ['DE'], 'regions_json' => []]);

    expect(app(ShippingCalculator::class)->getMatchingZone($store, ['country_code' => 'FR']))->toBeNull();
});

it('calculates a flat rate', function () {
    $store = Store::factory()->create();
    $product = Product::factory()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500]);
    $cart = Cart::factory()->create(['store_id' => $store->id]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 2500]);
    $rate = ShippingRate::make(['type' => 'flat', 'config_json' => ['amount' => 499]]);

    expect(app(ShippingCalculator::class)->calculate($rate, $cart))->toBe(499);
});

it('calculates a weight-based rate', function () {
    $store = Store::factory()->create();
    $product = Product::factory()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'weight_g' => 750]);
    $cart = Cart::factory()->create(['store_id' => $store->id]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1]);
    $rate = ShippingRate::make(['type' => 'weight', 'config_json' => ['ranges' => [
        ['min_g' => 0, 'max_g' => 500, 'amount' => 499],
        ['min_g' => 501, 'max_g' => 2000, 'amount' => 899],
    ]]]);

    expect(app(ShippingCalculator::class)->calculate($rate, $cart))->toBe(899);
});

it('calculates a price-based rate', function () {
    $store = Store::factory()->create();
    $product = Product::factory()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 7500]);
    $cart = Cart::factory()->create(['store_id' => $store->id]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 7500]);
    $rate = ShippingRate::make(['type' => 'price', 'config_json' => ['ranges' => [
        ['min_amount' => 0, 'max_amount' => 5000, 'amount' => 799],
        ['min_amount' => 5001, 'max_amount' => 999999, 'amount' => 399],
    ]]]);

    expect(app(ShippingCalculator::class)->calculate($rate, $cart))->toBe(399);
});

it('returns zero shipping when no items require shipping', function () {
    $store = Store::factory()->create();
    $product = Product::factory()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'requires_shipping' => false]);
    $cart = Cart::factory()->create(['store_id' => $store->id]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1]);
    $rate = ShippingRate::make(['type' => 'flat', 'config_json' => ['amount' => 499]]);

    expect(app(ShippingCalculator::class)->calculate($rate, $cart))->toBe(0);
});
