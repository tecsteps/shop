<?php

use App\Enums\StoreStatus;
use App\Models\Product;
use App\Models\Store;

/**
 * Styled error pages (spec 04 §13): the storefront-themed 404 and 503
 * pages render for unknown resources and suspended stores.
 */
function errorPageUrl(Store $store, string $path = '/'): string
{
    return 'http://'.$store->handle.'.test'.$path;
}

test('unknown product renders the styled 404 page with search and home links', function () {
    $store = $this->createStore();

    $response = $this->get(errorPageUrl($store, '/products/no-such-product'));

    $response->assertNotFound()
        ->assertSee('Page not found')
        ->assertSee("doesn't exist or has been moved", false)
        ->assertSee('action="/search"', false)
        ->assertSee('Go to home page')
        ->assertSee($store->name);
});

test('unknown storefront domain renders the styled 404 page', function () {
    $this->createStore();

    $this->get('http://no-such-store.test/')
        ->assertNotFound()
        ->assertSee('Page not found');
});

test('suspended store renders the styled 503 page with the unavailable copy', function () {
    $store = $this->createStore(['status' => StoreStatus::Suspended]);

    $response = $this->get(errorPageUrl($store));

    $response->assertStatus(503)
        ->assertSee("We'll be back soon")
        ->assertSee('This store is currently unavailable.')
        ->assertSee($store->name);
});

test('active store does not see the 503 page', function () {
    $store = $this->createStore();
    Product::factory()->active()->withVariants(1)->create(['store_id' => $store->id]);

    $this->get(errorPageUrl($store))->assertOk();
});
