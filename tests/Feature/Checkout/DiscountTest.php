<?php

use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Services\CartService;
use App\Services\CheckoutService;

/**
 * Build the absolute storefront API URL for the store's primary domain.
 */
function discountApiUrl(Store $store, string $path): string
{
    return 'http://'.$store->handle.'.test/api/storefront/v1'.$path;
}

/**
 * Store with variant (2500), DE zone + flat rate 499, and a checkout in
 * shipping_selected state for a 2x cart (subtotal 5000).
 *
 * @return array{0: Store, 1: ProductVariant, 2: ShippingRate, 3: App\Models\Checkout}
 */
function discountSetup(): array
{
    $store = test()->createStore();
    test()->bindStore($store);

    $product = Product::factory()->active()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->withInventory(50)->create([
        'product_id' => $product->id,
        'price_amount' => 2500,
    ]);

    $zone = ShippingZone::factory()->create(['store_id' => $store->id, 'countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->flat(499)->create(['zone_id' => $zone->id]);

    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $variant->id, 2);

    $service = app(CheckoutService::class);
    $checkout = $service->createFromCart($cart->refresh(), 'jane@example.com');
    $checkout = $service->setAddress($checkout, ['shipping_address' => [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'address1' => '123 Main St',
        'city' => 'Berlin',
        'country' => 'DE',
        'country_code' => 'DE',
        'postal_code' => '10115',
    ]]);
    $checkout = $service->setShippingMethod($checkout, $rate->id);

    return [$store, $variant, $rate, $checkout];
}

test('applies a valid percent discount code at checkout', function () {
    [$store, , , $checkout] = discountSetup();
    Discount::factory()->percent(10)->create(['store_id' => $store->id, 'code' => 'SAVE10']);

    $this->postJson(discountApiUrl($store, "/checkouts/{$checkout->id}/apply-discount"), ['code' => 'SAVE10'])
        ->assertOk()
        ->assertJsonPath('discount_code', 'SAVE10')
        ->assertJsonPath('totals.subtotal', 5000)
        ->assertJsonPath('totals.discount', 500)
        ->assertJsonPath('applied_discounts.0.code', 'SAVE10')
        ->assertJsonPath('applied_discounts.0.type', 'percent')
        ->assertJsonPath('applied_discounts.0.applied_amount', 500);
});

test('applies a valid fixed discount code at checkout', function () {
    [$store, , , $checkout] = discountSetup();
    Discount::factory()->fixed(500)->create(['store_id' => $store->id, 'code' => '5OFF']);

    $this->postJson(discountApiUrl($store, "/checkouts/{$checkout->id}/apply-discount"), ['code' => '5OFF'])
        ->assertOk()
        ->assertJsonPath('totals.discount', 500);
});

test('removes discount when code is cleared', function () {
    [$store, , , $checkout] = discountSetup();
    Discount::factory()->percent(10)->create(['store_id' => $store->id, 'code' => 'SAVE10']);
    $service = app(CheckoutService::class);
    $service->applyDiscount($checkout, 'SAVE10');

    expect($checkout->refresh()->totals_json['discount'])->toBe(500);

    $this->deleteJson(discountApiUrl($store, "/checkouts/{$checkout->id}/discount"))
        ->assertOk()
        ->assertJsonPath('discount_code', null)
        ->assertJsonPath('totals.discount', 0);
});

test('rejects expired discount at checkout', function () {
    [$store, , , $checkout] = discountSetup();
    Discount::factory()->expired()->create(['store_id' => $store->id, 'code' => 'OLD10']);

    $this->postJson(discountApiUrl($store, "/checkouts/{$checkout->id}/apply-discount"), ['code' => 'OLD10'])
        ->assertStatus(400)
        ->assertJsonPath('error_code', 'discount_expired');
});

test('rejects a discount that reached its usage limit', function () {
    [$store, , , $checkout] = discountSetup();
    Discount::factory()->maxedOut()->create(['store_id' => $store->id, 'code' => 'MAXED']);

    $this->postJson(discountApiUrl($store, "/checkouts/{$checkout->id}/apply-discount"), ['code' => 'MAXED'])
        ->assertStatus(400)
        ->assertJsonPath('error_code', 'discount_usage_limit_reached');
});

test('rejects an unknown discount code', function () {
    [$store, , , $checkout] = discountSetup();

    $this->postJson(discountApiUrl($store, "/checkouts/{$checkout->id}/apply-discount"), ['code' => 'NOPE'])
        ->assertUnprocessable()
        ->assertJsonPath('error_code', 'discount_not_found');
});

test('handles free shipping discount at checkout', function () {
    [$store, , , $checkout] = discountSetup();
    Discount::factory()->freeShipping()->create(['store_id' => $store->id, 'code' => 'FREESHIP']);

    $response = $this->postJson(discountApiUrl($store, "/checkouts/{$checkout->id}/apply-discount"), ['code' => 'FREESHIP']);

    $response->assertOk()
        ->assertJsonPath('totals.shipping', 0)
        ->assertJsonPath('shipping_method_id', $checkout->shipping_method_id);
});

test('applies automatic discounts stacked on the remaining amount', function () {
    [$store, , , $checkout] = discountSetup();
    Discount::factory()->automatic()->percent(10)->create(['store_id' => $store->id]);
    Discount::factory()->automatic()->percent(10)->create(['store_id' => $store->id]);

    app(CheckoutService::class)->recalculate($checkout->refresh());

    // 10% of 5000 = 500, then 10% of remaining 4500 = 450 -> 950 total.
    expect($checkout->refresh()->totals_json['discount'])->toBe(950);
});
