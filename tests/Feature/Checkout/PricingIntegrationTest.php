<?php

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Enums\TaxMode;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\TaxSettings;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\PricingEngine;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->store = $this->ctx['store'];
    $this->checkoutService = app(CheckoutService::class);
    $this->cartService = app(CartService::class);
    $this->pricingEngine = app(PricingEngine::class);
});

function createCheckoutWithItems($store, array $items = [['price' => 2500, 'quantity' => 2]]): Checkout
{
    $cart = app(CartService::class)->create($store);

    foreach ($items as $item) {
        $product = Product::factory()->active()->create(['store_id' => $store->id]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price_amount' => $item['price'],
        ]);
        app(CartService::class)->addLine($cart, $variant->id, $item['quantity']);
    }

    return app(CheckoutService::class)->createFromCart($cart->fresh('lines'));
}

it('calculates correct totals for a simple checkout', function () {
    $checkout = createCheckoutWithItems($this->store, [['price' => 2500, 'quantity' => 2]]);

    // Set address
    $checkout = $this->checkoutService->setAddress($checkout, [
        'email' => 'test@example.com',
        'shipping_address' => [
            'first_name' => 'John', 'last_name' => 'Doe',
            'address1' => '123 Main St', 'city' => 'New York',
            'country' => 'US', 'postal_code' => '10001',
        ],
    ]);

    // Set up flat shipping
    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id, 'countries_json' => ['US']]);
    $rate = ShippingRate::factory()->create(['zone_id' => $zone->id, 'config_json' => ['amount' => 499]]);
    $checkout = $this->checkoutService->setShippingMethod($checkout, $rate->id);

    // Set up exclusive tax
    TaxSettings::create([
        'store_id' => $this->store->id,
        'mode' => TaxMode::Manual,
        'provider' => 'none',
        'prices_include_tax' => false,
        'config_json' => ['default_rate' => 1900, 'tax_name' => 'VAT'],
    ]);

    $result = $this->pricingEngine->calculate($checkout->fresh());

    expect($result->subtotal)->toBe(5000)
        ->and($result->shipping)->toBe(499)
        ->and($result->taxTotal)->toBeGreaterThan(0)
        ->and($result->total)->toBe($result->subtotal - $result->discount + $result->shipping + $result->taxTotal);
});

it('applies discount code and recalculates', function () {
    $checkout = createCheckoutWithItems($this->store, [['price' => 5000, 'quantity' => 2]]);

    Discount::factory()->create([
        'store_id' => $this->store->id,
        'type' => DiscountType::Code,
        'code' => 'SAVE10',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'status' => DiscountStatus::Active,
    ]);

    $checkout->update(['discount_code' => 'SAVE10']);

    $result = $this->pricingEngine->calculate($checkout->fresh());

    expect($result->subtotal)->toBe(10000)
        ->and($result->discount)->toBe(1000);
});

it('stores pricing snapshot in totals_json', function () {
    $checkout = createCheckoutWithItems($this->store, [['price' => 2500, 'quantity' => 1]]);

    $checkout = $this->checkoutService->setAddress($checkout, [
        'email' => 'test@example.com',
        'shipping_address' => [
            'first_name' => 'John', 'last_name' => 'Doe',
            'address1' => '123 Main St', 'city' => 'Berlin',
            'country' => 'DE', 'postal_code' => '10115',
        ],
    ]);

    $checkout->refresh();

    expect($checkout->totals_json)->not->toBeNull()
        ->and($checkout->totals_json)->toHaveKeys(['subtotal', 'discount', 'shipping', 'total', 'currency']);
});

it('recalculates on shipping method change', function () {
    $checkout = createCheckoutWithItems($this->store, [['price' => 2500, 'quantity' => 2]]);

    $checkout = $this->checkoutService->setAddress($checkout, [
        'email' => 'test@example.com',
        'shipping_address' => [
            'first_name' => 'John', 'last_name' => 'Doe',
            'address1' => '123 Main St', 'city' => 'New York',
            'country' => 'US', 'postal_code' => '10001',
        ],
    ]);

    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id, 'countries_json' => ['US']]);
    $rate1 = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'name' => 'Standard',
        'config_json' => ['amount' => 499],
    ]);
    $rate2 = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'name' => 'Express',
        'config_json' => ['amount' => 1299],
    ]);

    $checkout = $this->checkoutService->setShippingMethod($checkout, $rate1->id);
    $totals1 = $checkout->totals_json;

    $checkout = $this->checkoutService->setShippingMethod($checkout, $rate2->id);
    $totals2 = $checkout->totals_json;

    expect($totals2['shipping'])->toBe(1299)
        ->and($totals2['shipping'])->not->toBe($totals1['shipping']);
});

it('handles prices-include-tax correctly', function () {
    $checkout = createCheckoutWithItems($this->store, [['price' => 11900, 'quantity' => 1]]);

    TaxSettings::create([
        'store_id' => $this->store->id,
        'mode' => TaxMode::Manual,
        'provider' => 'none',
        'prices_include_tax' => true,
        'config_json' => ['default_rate' => 1900, 'tax_name' => 'VAT'],
    ]);

    $checkout = $this->checkoutService->setAddress($checkout, [
        'email' => 'test@example.com',
        'shipping_address' => [
            'first_name' => 'John', 'last_name' => 'Doe',
            'address1' => '123 Main St', 'city' => 'Berlin',
            'country' => 'DE', 'postal_code' => '10115',
        ],
    ]);

    $result = $this->pricingEngine->calculate($checkout->fresh());

    // Tax should be extracted from gross: 11900 -> net ~10000, tax ~1900
    expect($result->subtotal)->toBe(11900)
        ->and($result->taxTotal)->toBe(1900);
});
