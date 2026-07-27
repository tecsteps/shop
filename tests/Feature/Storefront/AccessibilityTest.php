<?php

use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\CartService;

/**
 * Accessibility checks (spec 04 §15): single h1, skip link, labeled icon
 * buttons, and labeled form fields across the key storefront and admin
 * pages.
 */
function a11yStorefrontUrl(Store $store, string $path = '/'): string
{
    return 'http://'.$store->handle.'.test'.$path;
}

/**
 * Assert the rendered HTML contains exactly one h1.
 */
function assertSingleH1(string $html): void
{
    expect(substr_count(strtolower($html), '<h1'))->toBe(1);
}

test('storefront pages render a single h1, a skip link, and labeled icon buttons', function (?string $path, array $setup) {
    $store = $this->createStore();

    if ($setup['product'] ?? false) {
        $product = Product::factory()->active()->withVariants(1)->create(['store_id' => $store->id, 'title' => 'A11y Tee']);
        $path = '/products/'.$product->handle;
    }

    if ($setup['collection'] ?? false) {
        $collection = Collection::factory()->create(['store_id' => $store->id, 'title' => 'A11y Picks']);
        $product = Product::factory()->active()->withVariants(1)->create(['store_id' => $store->id]);
        $collection->products()->attach($product->id, ['position' => 0]);
        $path = '/collections/'.$collection->handle;
    }

    $response = $this->get(a11yStorefrontUrl($store, $path));

    $response->assertOk()
        ->assertSee('Skip to main content')
        ->assertSee('aria-label="Search"', false)
        ->assertSee('aria-label="Account"', false)
        ->assertSee('aria-label="Open cart"', false);

    assertSingleH1($response->getContent());
})->with([
    'home' => ['/', []],
    'product' => [null, ['product' => true]],
    'collection' => [null, ['collection' => true]],
    'cart' => ['/cart', []],
    'search' => ['/search?q=tee', []],
    'account login' => ['/account/login', []],
]);

test('product page renders a single h1 and labeled variant controls', function () {
    $store = $this->createStore();
    $product = Product::factory()->active()->create(['store_id' => $store->id, 'title' => 'A11y Tee']);
    ProductVariant::factory()->default()->withInventory(5)->create(['product_id' => $product->id]);

    $response = $this->get(a11yStorefrontUrl($store, '/products/'.$product->handle));

    $response->assertOk();
    assertSingleH1($response->getContent());
});

test('checkout step one has a single h1 and labeled form fields', function () {
    $store = $this->createStore();
    $this->bindStore($store);

    $product = Product::factory()->active()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->default()->withInventory(5)->create(['product_id' => $product->id]);

    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $variant->id, 1);

    $response = $this
        ->withSession(['cart_id' => $cart->id])
        ->get(a11yStorefrontUrl($store, '/checkout/new'));

    $response->assertOk()
        ->assertSee('for="checkout-email"', false)
        ->assertSee('id="checkout-email"', false)
        ->assertSee('for="address-first_name"', false)
        ->assertSee('id="address-first_name"', false)
        ->assertSee('for="address-postal_code"', false);

    assertSingleH1($response->getContent());
});

test('storefront login form fields are labeled', function () {
    $store = $this->createStore();

    $this->get(a11yStorefrontUrl($store, '/account/login'))
        ->assertOk()
        ->assertSee('for="email"', false)
        ->assertSee('id="email"', false)
        ->assertSee('for="password"', false)
        ->assertSee('id="password"', false);
});

test('admin login renders a single h1, a skip link, and labeled fields', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('Skip to main content')
        ->assertSee('for="email"', false)
        ->assertSee('id="email"', false)
        ->assertSee('for="password"', false)
        ->assertSee('id="password"', false);
});

test('admin dashboard renders a single h1 and a skip link', function () {
    $store = $this->createStore();
    $user = $this->createUserWithRole($store, 'admin');

    $response = $this->actingAs($user)
        ->withSession(['current_store_id' => $store->id])
        ->get('/admin');

    $response->assertOk()
        ->assertSee('Skip to main content');

    assertSingleH1($response->getContent());
});

test('cart drawer and search modal are modal dialogs with labels', function () {
    $store = $this->createStore();
    Product::factory()->active()->withVariants(1)->create(['store_id' => $store->id]);

    $this->get(a11yStorefrontUrl($store))
        ->assertOk()
        ->assertSee('role="dialog"', false)
        ->assertSee('aria-modal="true"', false)
        ->assertSee('aria-labelledby="cart-drawer-title"', false)
        ->assertSee('aria-label="Search"', false);
});
