<?php

use App\Enums\CartStatus;
use App\Enums\ShippingRateType;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\ShippingCalculator;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->store = $this->ctx['store'];
    $this->shippingCalculator = app(ShippingCalculator::class);
});

it('returns available shipping rates for address', function () {
    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['DE'],
    ]);
    ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'name' => 'Standard',
        'config_json' => ['amount' => 499],
    ]);

    $rates = $this->shippingCalculator->getAvailableRates($this->store, ['country' => 'DE']);

    expect($rates)->toHaveCount(1)
        ->and($rates->first()->name)->toBe('Standard')
        ->and($rates->first()->amount)->toBe(499);
});

it('returns empty when no zone matches address', function () {
    ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['DE'],
    ]);

    $rates = $this->shippingCalculator->getAvailableRates($this->store, ['country' => 'FR']);

    expect($rates)->toBeEmpty();
});

it('calculates flat rate correctly', function () {
    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id, 'countries_json' => ['US']]);
    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 499],
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id, 'status' => CartStatus::Active]);

    $result = $this->shippingCalculator->calculate($rate, $cart);

    expect($result)->toBe(499);
});

it('calculates weight-based rate correctly', function () {
    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id, 'countries_json' => ['US']]);
    $rate = ShippingRate::factory()->weightBased()->create([
        'zone_id' => $zone->id,
        'config_json' => [
            'ranges' => [
                ['min_g' => 0, 'max_g' => 500, 'amount' => 499],
                ['min_g' => 501, 'max_g' => 2000, 'amount' => 899],
            ],
        ],
    ]);

    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'weight_g' => 750,
        'requires_shipping' => true,
    ]);

    $cart = Cart::factory()->create(['store_id' => $this->store->id, 'status' => CartStatus::Active]);
    CartLine::factory()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    $result = $this->shippingCalculator->calculate($rate, $cart);

    expect($result)->toBe(899);
});

it('returns zero shipping when all items are digital', function () {
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'requires_shipping' => false,
        'weight_g' => 0,
    ]);

    $cart = app(CartService::class)->create($this->store);
    app(CartService::class)->addLine($cart, $variant->id, 1);

    // With no shipping required, checkout should handle it
    $checkout = app(CheckoutService::class)->createFromCart($cart->fresh('lines'));
    $checkout = app(CheckoutService::class)->setAddress($checkout, [
        'email' => 'test@example.com',
        'shipping_address' => [
            'first_name' => 'John', 'last_name' => 'Doe',
            'address1' => '123 Main St', 'city' => 'NYC',
            'country' => 'US', 'postal_code' => '10001',
        ],
    ]);

    // Digital items: setShippingMethod with null skips shipping
    $checkout = app(CheckoutService::class)->setShippingMethod($checkout, null);

    expect($checkout->status->value)->toBe('shipping_selected')
        ->and($checkout->shipping_method_id)->toBeNull();
});
