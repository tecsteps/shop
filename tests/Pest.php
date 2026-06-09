<?php

use App\Enums\InventoryPolicy;
use App\Enums\StoreUserRole;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

/**
 * Create a full store context: Organization, Store, StoreDomain, and a User
 * with the Owner role. Binds the store in the container as "current_store".
 *
 * @param  array<string, mixed>  $storeAttributes
 * @return array{organization: Organization, store: Store, domain: StoreDomain, user: User}
 */
function createStoreContext(array $storeAttributes = []): array
{
    $organization = Organization::factory()->create();
    $store = Store::factory()->for($organization)->create($storeAttributes);
    $domain = StoreDomain::factory()->for($store)->create();

    $user = User::factory()->create();

    StoreUser::query()->create([
        'store_id' => $store->getKey(),
        'user_id' => $user->getKey(),
        'role' => StoreUserRole::Owner,
    ]);

    app()->instance('current_store', $store);

    return [
        'organization' => $organization,
        'store' => $store,
        'domain' => $domain,
        'user' => $user,
    ];
}

/**
 * Create an active product with a single active default variant (and an
 * inventory item) for the given store. Used by cart and checkout tests.
 *
 * @param  array<string, mixed>  $variantAttributes
 */
function createPurchasableVariant(
    Store $store,
    int $priceAmount = 2500,
    int $quantityOnHand = 100,
    array $variantAttributes = [],
    InventoryPolicy $policy = InventoryPolicy::Deny,
): ProductVariant {
    $product = Product::factory()->active()->for($store)->create();

    $variant = ProductVariant::factory()
        ->asDefault()
        ->priced($priceAmount)
        ->for($product)
        ->create($variantAttributes);

    InventoryItem::factory()
        ->forVariant($variant)
        ->withStock($quantityOnHand)
        ->create(['policy' => $policy]);

    return $variant;
}

/**
 * A complete, valid checkout shipping address (Germany by default).
 *
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function validShippingAddress(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Erika',
        'last_name' => 'Mustermann',
        'address1' => 'Musterstrasse 1',
        'city' => 'Berlin',
        'postal_code' => '10115',
        'country_code' => 'DE',
    ], $overrides);
}

/**
 * Authenticate as an admin user and put their store in the session.
 */
function actingAsAdmin(User $user, ?Store $store = null): TestCase
{
    $store ??= $user->stores()->first();

    return test()
        ->actingAs($user)
        ->withSession(['current_store_id' => $store?->getKey()]);
}

/**
 * Authenticate as a storefront customer via the customer guard.
 */
function actingAsCustomer(Customer $customer): TestCase
{
    return test()->actingAs($customer, 'customer');
}
