<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
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

function createStoreContext(): array
{
    $org = \App\Models\Organization::factory()->create();
    $store = \App\Models\Store::factory()->for($org)->create();
    $domain = \App\Models\StoreDomain::factory()->for($store)->create(['domain' => 'shop.test', 'is_primary' => true]);
    $user = \App\Models\User::factory()->create();
    $store->users()->attach($user, ['role' => \App\Enums\StoreUserRole::Owner->value]);
    $settings = \App\Models\StoreSettings::factory()->create(['store_id' => $store->id]);
    app()->instance('current_store', $store);

    return compact('org', 'store', 'domain', 'user', 'settings');
}

function actingAsAdmin(\App\Models\User $user)
{
    return test()->actingAs($user);
}

function actingAsCustomer(\App\Models\Customer $customer)
{
    return test()->actingAs($customer, 'customer');
}

/**
 * Create a cart with a product, variant, and cart line for testing.
 *
 * @param  array<string, mixed>  $productOverrides
 * @param  array<string, mixed>  $variantOverrides
 * @return array{cart: \App\Models\Cart, product: \App\Models\Product, variant: \App\Models\ProductVariant, line: \App\Models\CartLine}
 */
function createCartWithProduct(
    \App\Models\Store $store,
    int $priceAmount = 2500,
    int $quantity = 1,
    int $inventoryQuantity = 0,
    array $productOverrides = [],
    array $variantOverrides = [],
): array {
    $cart = \App\Models\Cart::factory()->for($store)->create();
    $product = \App\Models\Product::factory()->active()->for($store)->create($productOverrides);
    $variant = \App\Models\ProductVariant::factory()->for($product)->create(array_merge(['price_amount' => $priceAmount], $variantOverrides));

    if ($inventoryQuantity > 0) {
        \App\Models\InventoryItem::factory()->create([
            'store_id' => $store->id,
            'variant_id' => $variant->id,
            'quantity_on_hand' => $inventoryQuantity,
        ]);
    }

    $subtotal = $priceAmount * $quantity;
    $line = \App\Models\CartLine::factory()->for($cart)->create([
        'variant_id' => $variant->id,
        'quantity' => $quantity,
        'unit_price' => $priceAmount,
        'subtotal' => $subtotal,
        'total' => $subtotal,
    ]);

    return compact('cart', 'product', 'variant', 'line');
}
