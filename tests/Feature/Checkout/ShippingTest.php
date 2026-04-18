<?php

use App\Enums\InventoryPolicy;
use App\Enums\ShippingRateType;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\ShippingCalculator;

beforeEach(function (): void {
    $ctx = $this->createStoreContext();
    $this->store = $ctx['store'];
    $this->calc = app(ShippingCalculator::class);
});

it('matches a country-only zone when no region given', function (): void {
    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['US'],
        'regions_json' => [],
    ]);

    $matched = $this->calc->getMatchingZone($this->store, ['country_code' => 'US']);
    expect($matched?->id)->toBe($zone->id);
});

it('prefers region-specific match over country-only', function (): void {
    $country = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['US'],
        'regions_json' => [],
    ]);

    $specific = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['US'],
        'regions_json' => ['US-CA'],
    ]);

    $matched = $this->calc->getMatchingZone($this->store, [
        'country_code' => 'US',
        'province_code' => 'US-CA',
    ]);

    expect($matched?->id)->toBe($specific->id);
});

it('returns null when no zone matches the country', function (): void {
    ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['US'],
    ]);

    expect($this->calc->getMatchingZone($this->store, ['country_code' => 'DE']))->toBeNull();
});

it('calculates a flat rate regardless of cart size', function (): void {
    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id, 'countries_json' => ['US']]);
    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 799],
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);

    expect($this->calc->calculate($rate, $cart))->toBe(799);
});

it('applies weight-based rates to physical-only cart', function (): void {
    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id, 'countries_json' => ['US']]);
    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'type' => ShippingRateType::Weight,
        'config_json' => [
            'ranges' => [
                ['min_g' => 0, 'max_g' => 500, 'amount' => 500],
                ['min_g' => 501, 'max_g' => 5000, 'amount' => 1000],
            ],
        ],
    ]);

    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'weight_g' => 300,
        'requires_shipping' => true,
        'price_amount' => 1000,
    ]);
    InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 100,
        'policy' => InventoryPolicy::Deny,
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price_amount' => 1000,
        'line_subtotal_amount' => 2000,
        'line_total_amount' => 2000,
    ]);

    // 600g total -> second range (1000)
    expect($this->calc->calculate($rate->fresh(), $cart->fresh()))->toBe(1000);
});

it('applies price-based rates with open-ended max', function (): void {
    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id, 'countries_json' => ['US']]);
    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'type' => ShippingRateType::Price,
        'config_json' => [
            'ranges' => [
                ['min_amount' => 0, 'max_amount' => 5000, 'amount' => 799],
                ['min_amount' => 5001, 'amount' => 0],
            ],
        ],
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 6000]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 6000,
        'line_subtotal_amount' => 6000,
        'line_total_amount' => 6000,
    ]);

    expect($this->calc->calculate($rate, $cart->fresh()))->toBe(0);
});
