<?php

use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\CheckoutService;
use App\Services\ShippingCalculator;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->calculator = new ShippingCalculator;
    $this->checkoutService = app(CheckoutService::class);
});

it('returns available shipping rates for address', function () {
    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['DE'],
    ]);
    ShippingRate::factory()->create(['zone_id' => $zone->id, 'name' => 'Standard', 'config_json' => ['amount' => 499]]);

    $rates = $this->calculator->getAvailableRates($this->store, ['country_code' => 'DE']);

    expect($rates)->toHaveCount(1)
        ->and($rates->first()->name)->toBe('Standard');
});

it('returns empty when no zone matches address', function () {
    ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['DE'],
    ]);

    $rates = $this->calculator->getAvailableRates($this->store, ['country_code' => 'FR']);

    expect($rates)->toBeEmpty();
});

it('calculates flat rate correctly', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'requires_shipping' => true]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 2500, 'line_subtotal_amount' => 2500, 'line_total_amount' => 2500]);

    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id, 'countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id, 'config_json' => ['amount' => 499]]);

    $checkout = $this->checkoutService->createFromCart($cart);
    $checkout = $this->checkoutService->setAddress($checkout, [
        'first_name' => 'John', 'last_name' => 'Doe', 'address1' => 'Street 1',
        'city' => 'Berlin', 'country_code' => 'DE', 'zip' => '10115',
    ]);
    $checkout = $this->checkoutService->setShippingMethod($checkout, $rate->id);

    expect($checkout->totals_json['shipping'])->toBe(499);
});

it('calculates weight-based rate correctly', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'requires_shipping' => true, 'weight_g' => 750]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 2500, 'line_subtotal_amount' => 2500, 'line_total_amount' => 2500]);

    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id, 'countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->weightBased()->create(['zone_id' => $zone->id]);

    $checkout = $this->checkoutService->createFromCart($cart);
    $checkout = $this->checkoutService->setAddress($checkout, [
        'first_name' => 'John', 'last_name' => 'Doe', 'address1' => 'Street 1',
        'city' => 'Berlin', 'country_code' => 'DE', 'zip' => '10115',
    ]);
    $checkout = $this->checkoutService->setShippingMethod($checkout, $rate->id);

    expect($checkout->totals_json['shipping'])->toBe(899);
});

it('returns zero shipping when all items are digital', function () {
    $cart = Cart::factory()->create(['store_id' => $this->store->id]);
    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'requires_shipping' => false]);
    CartLine::factory()->create(['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 1, 'unit_price_amount' => 2500, 'line_subtotal_amount' => 2500, 'line_total_amount' => 2500]);

    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id, 'countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id, 'config_json' => ['amount' => 499]]);

    $checkout = $this->checkoutService->createFromCart($cart);
    $checkout = $this->checkoutService->setAddress($checkout, [
        'first_name' => 'John', 'last_name' => 'Doe', 'address1' => 'Street 1',
        'city' => 'Berlin', 'country_code' => 'DE', 'zip' => '10115',
    ]);
    $checkout = $this->checkoutService->setShippingMethod($checkout, $rate->id);

    expect($checkout->totals_json['shipping'])->toBe(0);
});
