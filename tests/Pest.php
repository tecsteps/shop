<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithStore;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class, InteractsWithStore::class)
    ->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

/**
 * @return array{store: \App\Models\Store, user: \App\Models\User, organization: \App\Models\Organization, domain: \App\Models\StoreDomain}
 */
function createStoreContext(): array
{
    $organization = \App\Models\Organization::factory()->create();
    $store = \App\Models\Store::factory()->create(['organization_id' => $organization->id]);
    $domain = \App\Models\StoreDomain::factory()->create(['store_id' => $store->id]);
    $user = \App\Models\User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    app()->instance('current_store', $store);

    return ['store' => $store, 'user' => $user, 'organization' => $organization, 'domain' => $domain];
}

function bindCurrentStore(\App\Models\Store $store): \App\Models\Store
{
    app()->instance('current_store', $store);

    return $store;
}

/**
 * Build and complete a credit-card checkout, returning the created Order.
 */
function makeCompletedOrder(): \App\Models\Order
{
    $ctx = createStoreContext();
    $store = $ctx['store'];
    $product = app(\App\Services\ProductService::class)->create($store, ['title' => 'Widget', 'price_amount' => 2500, 'quantity_on_hand' => 10]);
    app(\App\Services\ProductService::class)->transitionStatus($product, \App\Enums\ProductStatus::Active);
    $zone = \App\Models\ShippingZone::factory()->create(['store_id' => $store->id, 'countries_json' => ['DE']]);
    $rate = \App\Models\ShippingRate::factory()->create(['zone_id' => $zone->id, 'type' => 'flat', 'config_json' => ['amount' => 499]]);
    $cart = app(\App\Services\CartService::class)->create($store);
    app(\App\Services\CartService::class)->addLine($cart, $product->variants()->first()->id, 2);
    $checkout = app(\App\Services\CheckoutService::class)->create($cart, 'customer@example.com');
    app(\App\Services\CheckoutService::class)->setAddress($checkout, [
        'email' => 'customer@example.com',
        'shipping_address' => ['first_name' => 'Jane', 'last_name' => 'Doe', 'address1' => 'Main 1', 'city' => 'Berlin', 'country' => 'Germany', 'country_code' => 'DE', 'postal_code' => '10115'],
    ]);
    app(\App\Services\CheckoutService::class)->setShippingMethod($checkout, $rate->id);
    app(\App\Services\CheckoutService::class)->selectPaymentMethod($checkout, 'credit_card');

    return app(\App\Services\CheckoutService::class)->completeCheckout($checkout, ['card_number' => '4242424242424242']);
}

