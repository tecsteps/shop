<?php

use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Enums\StoreDomainType;
use App\Enums\StoreUserRole;
use App\Enums\VariantStatus;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\User;
use App\Support\CurrentStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

beforeEach(function (): void {
    app(CurrentStore::class)->clear();
});

afterEach(function (): void {
    app(CurrentStore::class)->clear();
});

function setCurrentStore(Store $store): void
{
    app(CurrentStore::class)->set($store);
    app()->instance('current_store', $store);
    app()->instance('current_store_id', $store->id);
}

function createStoreWithDomain(?string $hostname = null): Store
{
    $store = Store::factory()->create();

    StoreDomain::factory()
        ->for($store)
        ->create([
            'hostname' => $hostname ?? Str::slug($store->handle).'.test',
            'type' => StoreDomainType::Storefront,
            'is_primary' => true,
        ]);

    return $store;
}

/**
 * @param  array<string, mixed>  $productAttributes
 * @param  array<string, mixed>  $variantAttributes
 * @param  array<string, mixed>  $inventoryAttributes
 */
function createSellableVariant(
    Store $store,
    array $productAttributes = [],
    array $variantAttributes = [],
    array $inventoryAttributes = [],
): ProductVariant {
    $product = Product::factory()
        ->for($store)
        ->state(array_merge([
            'status' => ProductStatus::Active,
            'published_at' => now(),
        ], $productAttributes))
        ->create();

    $variant = ProductVariant::factory()
        ->for($product)
        ->state(array_merge([
            'status' => VariantStatus::Active,
            'is_default' => true,
            'price_amount' => 1999,
            'currency' => $store->default_currency,
        ], $variantAttributes))
        ->create();

    InventoryItem::factory()
        ->for($store)
        ->for($variant, 'variant')
        ->state(array_merge([
            'quantity_on_hand' => 25,
            'quantity_reserved' => 0,
            'policy' => InventoryPolicy::Deny,
        ], $inventoryAttributes))
        ->create();

    return $variant->fresh(['product', 'inventoryItem']);
}

function createStoreAdmin(Store $store): User
{
    $user = User::factory()->create();

    DB::table('store_users')->insert([
        'store_id' => $store->id,
        'user_id' => $user->id,
        'role' => StoreUserRole::Owner->value,
        'created_at' => now(),
    ]);

    return $user;
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, string>
 */
function checkoutAddress(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'address1' => 'Main Street 1',
        'address2' => '',
        'company' => '',
        'city' => 'Berlin',
        'province' => '',
        'province_code' => '',
        'country' => 'Germany',
        'country_code' => 'DE',
        'zip' => '10115',
        'phone' => '+4930123456',
    ], $overrides);
}
