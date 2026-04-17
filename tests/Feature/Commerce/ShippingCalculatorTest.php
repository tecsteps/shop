<?php

use App\Enums\ShippingRateType;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Services\ShippingCalculator;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('matches an address to a country-only zone', function () {
    $store = Store::factory()->create();
    ShippingZone::factory()->create([
        'store_id' => $store->getKey(),
        'countries_json' => ['US'],
    ]);

    $zone = app(ShippingCalculator::class)->getMatchingZone($store, [
        'country_code' => 'US',
    ]);

    expect($zone)->not->toBeNull();
});

it('prefers a region-specific zone over country-only when both match', function () {
    $store = Store::factory()->create();
    $countryZone = ShippingZone::factory()->create([
        'store_id' => $store->getKey(),
        'countries_json' => ['US'],
    ]);
    $regionZone = ShippingZone::factory()->create([
        'store_id' => $store->getKey(),
        'countries_json' => ['US'],
        'regions_json' => ['US-CA'],
    ]);

    $zone = app(ShippingCalculator::class)->getMatchingZone($store, [
        'country_code' => 'US',
        'province_code' => 'CA',
    ]);

    expect($zone->getKey())->toBe($regionZone->getKey());
});

it('returns null when no zone matches', function () {
    $store = Store::factory()->create();

    expect(app(ShippingCalculator::class)->getMatchingZone($store, ['country_code' => 'ZZ']))->toBeNull();
});

it('lists active rates for the matching zone', function () {
    $store = Store::factory()->create();
    $zone = ShippingZone::factory()->create([
        'store_id' => $store->getKey(),
        'countries_json' => ['US'],
    ]);
    ShippingRate::factory()->create(['zone_id' => $zone->getKey(), 'is_active' => 1]);
    ShippingRate::factory()->create(['zone_id' => $zone->getKey(), 'is_active' => 0]);

    $rates = app(ShippingCalculator::class)->getAvailableRates($store, ['country_code' => 'US']);

    expect($rates)->toHaveCount(1);
});

it('calculates a flat rate amount', function () {
    [$cart, $rate] = makeShippingFixture(ShippingRateType::Flat, ['amount' => 799]);

    $amount = app(ShippingCalculator::class)->calculate($rate, $cart);

    expect($amount)->toBe(799);
});

it('calculates a weight-based rate using line weights', function () {
    [$cart, $rate] = makeShippingFixture(ShippingRateType::Weight, [
        'ranges' => [
            ['min_g' => 0, 'max_g' => 1000, 'amount' => 500],
            ['min_g' => 1001, 'max_g' => 5000, 'amount' => 1000],
        ],
    ], weight: 2000, quantity: 1);

    $amount = app(ShippingCalculator::class)->calculate($rate, $cart);

    expect($amount)->toBe(1000);
});

it('calculates a price-based rate using cart subtotal', function () {
    [$cart, $rate] = makeShippingFixture(ShippingRateType::Price, [
        'ranges' => [
            ['min_amount' => 0, 'max_amount' => 4999, 'amount' => 799],
            ['min_amount' => 5000, 'amount' => 0],
        ],
    ], price: 6000, quantity: 1);

    $amount = app(ShippingCalculator::class)->calculate($rate, $cart);

    expect($amount)->toBe(0);
});

it('returns zero shipping for all-digital carts', function () {
    $store = Store::factory()->create();
    $product = Product::factory()->active()->create(['store_id' => $store->getKey()]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->getKey(),
        'requires_shipping' => 0,
    ]);
    InventoryItem::factory()->create([
        'store_id' => $store->getKey(),
        'variant_id' => $variant->getKey(),
        'quantity_on_hand' => 10,
    ]);

    $cart = Cart::factory()->create(['store_id' => $store->getKey()]);
    CartLine::factory()->create([
        'cart_id' => $cart->getKey(),
        'variant_id' => $variant->getKey(),
        'unit_price_amount' => 1000,
        'quantity' => 1,
        'line_subtotal_amount' => 1000,
        'line_total_amount' => 1000,
    ]);

    $zone = ShippingZone::factory()->create(['store_id' => $store->getKey()]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->getKey()]);

    $amount = app(ShippingCalculator::class)->calculate($rate, $cart);

    expect($amount)->toBe(0);
});

function makeShippingFixture(ShippingRateType $type, array $config, int $weight = 500, int $price = 1000, int $quantity = 1): array
{
    $store = Store::factory()->create();
    $product = Product::factory()->active()->create(['store_id' => $store->getKey()]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->getKey(),
        'price_amount' => $price,
        'weight_g' => $weight,
        'requires_shipping' => 1,
    ]);
    InventoryItem::factory()->create([
        'store_id' => $store->getKey(),
        'variant_id' => $variant->getKey(),
        'quantity_on_hand' => 100,
    ]);

    $cart = Cart::factory()->create(['store_id' => $store->getKey()]);
    CartLine::factory()->create([
        'cart_id' => $cart->getKey(),
        'variant_id' => $variant->getKey(),
        'unit_price_amount' => $price,
        'quantity' => $quantity,
        'line_subtotal_amount' => $price * $quantity,
        'line_total_amount' => $price * $quantity,
    ]);

    $zone = ShippingZone::factory()->create(['store_id' => $store->getKey()]);
    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->getKey(),
        'type' => $type->value,
        'config_json' => $config,
    ]);

    return [$cart, $rate];
}
