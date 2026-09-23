<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

function shopStore(string $hostname = 'shop.test', string $currency = 'EUR'): \App\Models\Store
{
    $store = \App\Models\Store::factory()->create(['default_currency' => $currency]);
    $store->domains()->create([
        'hostname' => $hostname,
        'type' => 'storefront',
        'is_primary' => true,
        'tls_mode' => 'managed',
    ]);
    \Illuminate\Support\Facades\Cache::flush();

    return $store;
}

/** @return array{product: \App\Models\Product, variant: \App\Models\ProductVariant, inventory: \App\Models\InventoryItem} */
function shopProduct(\App\Models\Store $store, int $price = 2500, int $stock = 10, bool $requiresShipping = true): array
{
    $product = \App\Models\Product::factory()->create([
        'store_id' => $store->id,
        'title' => 'Test cotton shirt',
        'handle' => 'test-cotton-shirt-'.fake()->unique()->numberBetween(1, 100000),
        'status' => 'active',
        'published_at' => now(),
    ]);
    $variant = \App\Models\ProductVariant::factory()->for($product)->create([
        'currency' => $store->default_currency,
        'price_amount' => $price,
        'requires_shipping' => $requiresShipping,
    ]);
    $inventory = \App\Models\InventoryItem::factory()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => $stock,
        'quantity_reserved' => 0,
        'policy' => 'deny',
    ]);

    return ['product' => $product, 'variant' => $variant, 'inventory' => $inventory];
}
