<?php

use App\Enums\ShippingRateType;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Services\ShippingCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('prefers the most specific shipping zone and calculates weight rates', function () {
    $store = Store::factory()->create();
    ShippingZone::factory()->create(['store_id' => $store->id, 'countries_json' => ['US'], 'regions_json' => []]);
    $zone = ShippingZone::factory()->create(['store_id' => $store->id, 'countries_json' => ['US'], 'regions_json' => ['US-NY']]);
    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'type' => ShippingRateType::Weight,
        'config_json' => ['ranges' => [['min_g' => 0, 'max_g' => 1000, 'amount' => 500]]],
    ]);
    $product = Product::factory()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'weight_g' => 400]);
    $cart = Cart::factory()->create(['store_id' => $store->id]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 2]);

    $calculator = app(ShippingCalculator::class);

    expect($calculator->getAvailableRates($store, ['country_code' => 'US', 'province_code' => 'US-NY'])->pluck('id'))
        ->toContain($rate->id)
        ->and($calculator->calculate($rate, $cart->load('lines.variant')))->toBe(500);
});
