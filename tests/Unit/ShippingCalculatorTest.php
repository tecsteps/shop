<?php

use App\Enums\CartStatus;
use App\Enums\ShippingRateType;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\ShippingCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

function createShippingContext(): array
{
    $ctx = createStoreContext();
    $store = $ctx['store'];
    $calculator = new ShippingCalculator;

    return compact('store', 'calculator') + $ctx;
}

it('matches a zone by country code', function () {
    $ctx = createShippingContext();
    ShippingZone::factory()->create([
        'store_id' => $ctx['store']->id,
        'countries_json' => ['DE', 'AT', 'CH'],
    ]);

    $zone = $ctx['calculator']->getMatchingZone($ctx['store'], ['country' => 'DE']);

    expect($zone)->not->toBeNull()
        ->and($zone->countries_json)->toContain('DE');
});

it('matches a zone by region code', function () {
    $ctx = createShippingContext();
    ShippingZone::factory()->create([
        'store_id' => $ctx['store']->id,
        'countries_json' => ['US'],
        'regions_json' => ['US-NY', 'US-CA'],
    ]);

    $zone = $ctx['calculator']->getMatchingZone($ctx['store'], ['country' => 'US', 'province_code' => 'US-NY']);

    expect($zone)->not->toBeNull();
});

it('returns null when no zone matches the address', function () {
    $ctx = createShippingContext();
    ShippingZone::factory()->create([
        'store_id' => $ctx['store']->id,
        'countries_json' => ['DE'],
    ]);

    $zone = $ctx['calculator']->getMatchingZone($ctx['store'], ['country' => 'FR']);

    expect($zone)->toBeNull();
});

it('calculates a flat rate', function () {
    $ctx = createShippingContext();
    $rate = ShippingRate::factory()->create([
        'zone_id' => ShippingZone::factory()->create(['store_id' => $ctx['store']->id])->id,
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 499],
    ]);

    $cart = Cart::factory()->create(['store_id' => $ctx['store']->id, 'status' => CartStatus::Active]);

    $result = $ctx['calculator']->calculate($rate, $cart);

    expect($result)->toBe(499);
});

it('calculates a weight-based rate', function () {
    $ctx = createShippingContext();
    $rate = ShippingRate::factory()->weightBased()->create([
        'zone_id' => ShippingZone::factory()->create(['store_id' => $ctx['store']->id])->id,
        'config_json' => [
            'ranges' => [
                ['min_g' => 0, 'max_g' => 500, 'amount' => 499],
                ['min_g' => 501, 'max_g' => 2000, 'amount' => 899],
            ],
        ],
    ]);

    $product = Product::factory()->active()->create(['store_id' => $ctx['store']->id]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'weight_g' => 750,
        'requires_shipping' => true,
    ]);

    $cart = Cart::factory()->create(['store_id' => $ctx['store']->id, 'status' => CartStatus::Active]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    $result = $ctx['calculator']->calculate($rate, $cart);

    expect($result)->toBe(899);
});

it('calculates a price-based rate', function () {
    $ctx = createShippingContext();
    $rate = ShippingRate::factory()->priceBased()->create([
        'zone_id' => ShippingZone::factory()->create(['store_id' => $ctx['store']->id])->id,
        'config_json' => [
            'ranges' => [
                ['min_amount' => 0, 'max_amount' => 5000, 'amount' => 799],
                ['min_amount' => 5001, 'amount' => 399],
            ],
        ],
    ]);

    $product = Product::factory()->active()->create(['store_id' => $ctx['store']->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 7500]);
    $cart = Cart::factory()->create(['store_id' => $ctx['store']->id, 'status' => CartStatus::Active]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 7500,
        'line_subtotal_amount' => 7500,
        'line_total_amount' => 7500,
    ]);

    $result = $ctx['calculator']->calculate($rate, $cart);

    expect($result)->toBe(399);
});

it('returns zero shipping when no items require shipping', function () {
    $ctx = createShippingContext();
    $zone = ShippingZone::factory()->create(['store_id' => $ctx['store']->id, 'countries_json' => ['US']]);

    $rates = $ctx['calculator']->getAvailableRates($ctx['store'], ['country' => 'US']);

    // A cart where all items are digital (requires_shipping=false)
    $product = Product::factory()->active()->create(['store_id' => $ctx['store']->id]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'requires_shipping' => false,
        'weight_g' => 0,
    ]);

    $rate = ShippingRate::factory()->weightBased()->create([
        'zone_id' => $zone->id,
        'config_json' => [
            'ranges' => [
                ['min_g' => 0, 'max_g' => 0, 'amount' => 0],
                ['min_g' => 1, 'max_g' => 5000, 'amount' => 899],
            ],
        ],
    ]);

    $cart = Cart::factory()->create(['store_id' => $ctx['store']->id, 'status' => CartStatus::Active]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    $result = $ctx['calculator']->calculate($rate, $cart);

    // Weight is 0 because requires_shipping=false, matches 0-0 range
    expect($result)->toBe(0);
});

it('returns the correct rate when multiple zones match and the first is selected', function () {
    $ctx = createShippingContext();
    // Country-only zone
    $zone1 = ShippingZone::factory()->create([
        'store_id' => $ctx['store']->id,
        'name' => 'US General',
        'countries_json' => ['US'],
        'regions_json' => [],
    ]);
    ShippingRate::factory()->create(['zone_id' => $zone1->id, 'config_json' => ['amount' => 999]]);

    // Region-specific zone (higher specificity)
    $zone2 = ShippingZone::factory()->create([
        'store_id' => $ctx['store']->id,
        'name' => 'US-NY',
        'countries_json' => ['US'],
        'regions_json' => ['US-NY'],
    ]);
    ShippingRate::factory()->create(['zone_id' => $zone2->id, 'config_json' => ['amount' => 499]]);

    // Region-specific match should win
    $zone = $ctx['calculator']->getMatchingZone($ctx['store'], ['country' => 'US', 'province_code' => 'US-NY']);

    expect($zone)->not->toBeNull()
        ->and($zone->id)->toBe($zone2->id);
});

it('skips inactive rates', function () {
    $ctx = createShippingContext();
    $zone = ShippingZone::factory()->create(['store_id' => $ctx['store']->id, 'countries_json' => ['US']]);

    ShippingRate::factory()->create(['zone_id' => $zone->id, 'name' => 'Active Rate', 'is_active' => true]);
    ShippingRate::factory()->inactive()->create(['zone_id' => $zone->id, 'name' => 'Inactive Rate']);

    $rates = $ctx['calculator']->getAvailableRates($ctx['store'], ['country' => 'US']);

    expect($rates)->toHaveCount(1)
        ->and($rates->first()->name)->toBe('Active Rate');
});
