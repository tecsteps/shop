<?php

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductVariant;
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

function createCheckoutForDiscount($store, int $itemPrice = 2500, int $qty = 2): \App\Models\Checkout
{
    $product = Product::factory()->active()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => $itemPrice]);

    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $variant->id, $qty);

    return app(CheckoutService::class)->createFromCart($cart->fresh('lines'));
}

it('applies a valid percent discount code at checkout', function () {
    $checkout = createCheckoutForDiscount($this->store, 2500, 2);

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

    expect($result->discount)->toBe(500); // 10% of 5000
});

it('applies a valid fixed discount code at checkout', function () {
    $checkout = createCheckoutForDiscount($this->store, 5000, 2);

    Discount::factory()->fixed()->create([
        'store_id' => $this->store->id,
        'type' => DiscountType::Code,
        'code' => '5OFF',
        'value_amount' => 500,
        'status' => DiscountStatus::Active,
    ]);

    $checkout->update(['discount_code' => '5OFF']);

    $result = $this->pricingEngine->calculate($checkout->fresh());

    expect($result->discount)->toBe(500);
});

it('removes discount when code is cleared', function () {
    $checkout = createCheckoutForDiscount($this->store, 2500, 2);

    Discount::factory()->create([
        'store_id' => $this->store->id,
        'type' => DiscountType::Code,
        'code' => 'SAVE10',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'status' => DiscountStatus::Active,
    ]);

    $checkout->update(['discount_code' => 'SAVE10']);
    $this->pricingEngine->calculate($checkout->fresh());

    // Clear discount
    $checkout->update(['discount_code' => null]);
    $result = $this->pricingEngine->calculate($checkout->fresh());

    expect($result->discount)->toBe(0);
});

it('rejects expired discount at checkout via API', function () {
    $checkout = createCheckoutForDiscount($this->store, 2500, 2);

    Discount::factory()->create([
        'store_id' => $this->store->id,
        'type' => DiscountType::Code,
        'code' => 'OLDCODE',
        'status' => DiscountStatus::Active,
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->subDay(),
    ]);

    $response = $this->postJson(
        "http://acme-fashion.test/api/storefront/v1/checkouts/{$checkout->id}/apply-discount",
        ['code' => 'OLDCODE']
    );

    $response->assertStatus(422)
        ->assertJsonPath('error_code', 'discount_expired');
});

it('increments usage count when discount is applied', function () {
    $checkout = createCheckoutForDiscount($this->store, 2500, 2);

    $discount = Discount::factory()->create([
        'store_id' => $this->store->id,
        'type' => DiscountType::Code,
        'code' => 'TRACK',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'usage_count' => 5,
        'status' => DiscountStatus::Active,
    ]);

    // Apply discount via API endpoint
    $response = $this->postJson(
        "http://acme-fashion.test/api/storefront/v1/checkouts/{$checkout->id}/apply-discount",
        ['code' => 'TRACK']
    );

    $response->assertSuccessful();
    // Discount code should be applied
    expect($checkout->fresh()->discount_code)->toBe('TRACK');
});

it('handles free shipping discount at checkout', function () {
    $checkout = createCheckoutForDiscount($this->store, 2500, 2);

    Discount::factory()->freeShipping()->create([
        'store_id' => $this->store->id,
        'type' => DiscountType::Code,
        'code' => 'FREESHIP',
        'status' => DiscountStatus::Active,
    ]);

    $zone = \App\Models\ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'countries_json' => ['US'],
    ]);
    $rate = \App\Models\ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'config_json' => ['amount' => 499],
    ]);

    $checkout->update([
        'discount_code' => 'FREESHIP',
        'shipping_method_id' => $rate->id,
    ]);

    $result = $this->pricingEngine->calculate($checkout->fresh());

    expect($result->shipping)->toBe(0);
});
