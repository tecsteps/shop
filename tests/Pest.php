<?php

use App\Enums\StoreUserRole;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

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
 * Create a full store context: an organization, a store, a storefront domain,
 * and an owner user, then bind the store as the container's `current_store`.
 *
 * @param  array{
 *     hostname?: string,
 *     handle?: string,
 *     status?: \App\Enums\StoreStatus|string,
 *     ownerRole?: \App\Enums\StoreUserRole,
 *     bind?: bool,
 * }  $attributes
 * @return array{
 *     organization: \App\Models\Organization,
 *     store: \App\Models\Store,
 *     domain: \App\Models\StoreDomain,
 *     owner: \App\Models\User,
 * }
 */
function createStoreContext(array $attributes = []): array
{
    $hostname = $attributes['hostname'] ?? 'acme-fashion.test';

    $organization = Organization::factory()->create();

    $storeState = ['organization_id' => $organization->id];

    if (isset($attributes['handle'])) {
        $storeState['handle'] = $attributes['handle'];
    }

    if (isset($attributes['status'])) {
        $storeState['status'] = $attributes['status'] instanceof BackedEnum
            ? $attributes['status']->value
            : $attributes['status'];
    }

    $store = Store::factory()->create($storeState);

    $domain = StoreDomain::factory()->create([
        'store_id' => $store->id,
        'hostname' => $hostname,
    ]);

    $owner = User::factory()->create();

    $store->users()->attach($owner->id, [
        'role' => ($attributes['ownerRole'] ?? StoreUserRole::Owner)->value,
    ]);

    if ($attributes['bind'] ?? true) {
        bindCurrentStore($store);
    }

    return [
        'organization' => $organization,
        'store' => $store,
        'domain' => $domain,
        'owner' => $owner,
    ];
}

/**
 * Bind a store as the container's `current_store` (mimics ResolveStore).
 */
function bindCurrentStore(Store $store): Store
{
    app()->instance('current_store', $store);

    return $store;
}

/**
 * Build an absolute storefront URL for a hostname.
 *
 * The test client derives the request host from the URL (not from a Host
 * header), so storefront requests must target the full hostname for
 * ResolveStore to match a store_domains row.
 */
function storefrontUrl(string $hostname, string $path = '/'): string
{
    return 'http://'.$hostname.'/'.ltrim($path, '/');
}

/**
 * Authenticate as an admin via the web guard and set the session's current
 * store so admin (session-based) store resolution succeeds.
 */
function actingAsAdmin(User $user, ?Store $store = null): User
{
    test()->actingAs($user, 'web');

    $store ??= app()->bound('current_store') ? app('current_store') : null;

    if ($store !== null) {
        session()->put('current_store_id', $store->id);
    }

    return $user;
}

/**
 * Authenticate as a customer via the customer guard.
 */
function actingAsCustomer(Customer $customer): Customer
{
    test()->actingAs($customer, 'customer');

    return $customer;
}

/**
 * Insert a minimal order_lines row referencing a variant so catalog
 * order-reference guards (product delete, status revert, variant archival) can
 * be exercised in isolation.
 *
 * Phase 5 introduced the real order_lines table (with a NOT NULL order_id and
 * title_snapshot). This helper now creates a minimal backing order for the
 * variant's store and inserts a real order_lines row carrying the variant_id the
 * guards inspect.
 */
function fakeOrderLineFor(int $variantId): void
{
    $storeId = App\Models\ProductVariant::query()
        ->whereKey($variantId)
        ->join('products', 'products.id', '=', 'product_variants.product_id')
        ->value('products.store_id');

    $order = App\Models\Order::withoutGlobalScopes()->create([
        'store_id' => $storeId,
        'order_number' => '#'.fake()->unique()->numberBetween(100000, 999999),
        'payment_method' => 'credit_card',
        'status' => 'paid',
        'financial_status' => 'paid',
        'fulfillment_status' => 'unfulfilled',
        'currency' => 'USD',
        'total_amount' => 0,
        'placed_at' => now(),
    ]);

    App\Models\OrderLine::query()->create([
        'order_id' => $order->id,
        'store_id' => $storeId,
        'variant_id' => $variantId,
        'title_snapshot' => 'Test line',
        'quantity' => 1,
        'unit_price_amount' => 0,
        'total_amount' => 0,
    ]);
}

/**
 * Create an active, sellable variant in the current store with stock and a
 * loaded inventory item.
 *
 * @param  array{price?: int, on_hand?: int, policy?: string, requires_shipping?: bool, weight_g?: int, active?: bool}  $attributes
 */
function makeSellableVariant(array $attributes = []): App\Models\ProductVariant
{
    $store = app('current_store');

    $product = App\Models\Product::factory()->create([
        'store_id' => $store->id,
        'status' => ($attributes['active'] ?? true) ? 'active' : 'draft',
    ]);

    $variant = App\Models\ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => $attributes['price'] ?? 5000,
        'requires_shipping' => $attributes['requires_shipping'] ?? true,
        'weight_g' => $attributes['weight_g'] ?? 500,
    ]);

    $variant->inventoryItem->update([
        'quantity_on_hand' => $attributes['on_hand'] ?? 100,
        'policy' => $attributes['policy'] ?? 'continue',
    ]);

    return $variant->fresh('inventoryItem');
}

/**
 * Build a cart in the current store with the given [price, quantity] lines and
 * return it with relations loaded.
 *
 * @param  list<array{0: int, 1: int}>  $lines
 * @param  array{requires_shipping?: bool, weight_g?: int}  $variantAttributes
 */
function cartWithLines(array $lines, array $variantAttributes = []): App\Models\Cart
{
    $store = app('current_store');
    $cart = App\Models\Cart::factory()->create(['store_id' => $store->id, 'currency' => 'USD']);

    foreach ($lines as [$price, $quantity]) {
        $variant = makeSellableVariant(array_merge($variantAttributes, ['price' => $price]));
        $cart->lines()->create([
            'variant_id' => $variant->id,
            'quantity' => $quantity,
            'unit_price_amount' => $price,
            'line_subtotal_amount' => $price * $quantity,
            'line_discount_amount' => 0,
            'line_total_amount' => $price * $quantity,
        ]);
    }

    return $cart->load('lines.variant.product');
}

/**
 * Start a checkout from a single-line cart in the current store.
 *
 * @param  array{price?: int, requires_shipping?: bool, weight_g?: int}  $variantAttributes
 */
function startCheckout(array $variantAttributes = []): App\Models\Checkout
{
    $cart = cartWithLines([[$variantAttributes['price'] ?? 5000, 1]], $variantAttributes);

    return app(App\Services\CheckoutService::class)->startFromCart($cart);
}

/**
 * A valid German shipping address payload for setAddress().
 *
 * @return array{email: string, shipping_address: array<string, mixed>}
 */
function germanAddressData(string $email = 'buyer@example.com'): array
{
    return [
        'email' => $email,
        'shipping_address' => [
            'first_name' => 'Anna',
            'last_name' => 'Schmidt',
            'address1' => 'Hauptstrasse 1',
            'city' => 'Berlin',
            'country' => 'DE',
            'postal_code' => '10115',
            'province_code' => null,
        ],
    ];
}
