<?php

use App\Models\Collection;
use Tests\Support\CommerceTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('renders the home page for a store hostname', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();

    $response = $this->get(storefrontUrl($domain->hostname, '/'));

    $response->assertOk();
    $response->assertSee($store->name);
    $response->assertSee('Featured Products');
});

it('shows featured products and collections on the home page', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $variant = CommerceTestHelper::purchasableVariant($store);
    $collection = Collection::factory()->create(['store_id' => $store->id, 'status' => 'active']);

    $response = $this->get(storefrontUrl($domain->hostname, '/'));

    $response->assertOk();
    $response->assertSee($variant->product->title);
    $response->assertSee($collection->title);
});

it('does not show other stores products on the home page', function () {
    ['domain' => $domain] = $this->createStoreContext();
    ['store' => $otherStore] = $this->createStoreContext();
    $variant = CommerceTestHelper::purchasableVariant($otherStore, productOverrides: ['title' => 'Other Store Exclusive Product']);

    $response = $this->get(storefrontUrl($domain->hostname, '/'));

    $response->assertOk();
    $response->assertDontSee('Other Store Exclusive Product');
});

it('shows the cart icon with a count badge when the session cart has lines', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store);

    $response = $this->withSession(['cart_id' => $cart->id])->get(storefrontUrl($domain->hostname, '/'));

    $response->assertOk();
    $response->assertSee('aria-label="Open cart"', false);
});
