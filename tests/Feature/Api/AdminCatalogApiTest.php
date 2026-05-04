<?php

use App\Enums\MediaStatus;
use App\Enums\ProductStatus;
use App\Models\Collection as ProductCollection;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use App\Services\WebhookService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function adminCatalogApiStore(): Store
{
    return Store::query()->where('handle', 'acme-fashion')->firstOrFail();
}

function adminCatalogApiUser(): User
{
    return User::query()->where('email', 'admin@acme.test')->firstOrFail();
}

/**
 * @param  list<string>  $abilities
 * @return array{token: \App\Models\OauthToken, plain_text: string}
 */
function adminCatalogApiToken(Store $store, array $abilities): array
{
    return app(WebhookService::class)->createApiToken($store, 'Catalog integration', $abilities);
}

test('admin product api lists and shows store scoped products', function (): void {
    $store = adminCatalogApiStore();
    $product = Product::factory()
        ->withDefaultVariant(3299)
        ->create([
            'store_id' => $store->getKey(),
            'title' => 'Admin API Jacket',
            'handle' => 'admin-api-jacket',
            'vendor' => 'Catalog Test Vendor',
            'tags' => ['outerwear', 'api'],
        ]);
    $variant = ProductVariant::withoutGlobalScopes()
        ->where('product_id', $product->getKey())
        ->firstOrFail();
    Product::factory()
        ->withDefaultVariant()
        ->create([
            'store_id' => Store::factory()->create()->getKey(),
            'title' => 'Other Store Jacket',
        ]);

    $this->getJson("/api/admin/v1/stores/{$store->getKey()}/products")
        ->assertUnauthorized();

    $this->actingAs(adminCatalogApiUser())
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/products?query={$variant->sku}&sort=title_asc")
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Admin API Jacket')
        ->assertJsonPath('data.0.variants_count', 1)
        ->assertJsonPath('data.0.total_inventory', 50)
        ->assertJsonMissing(['title' => 'Other Store Jacket']);

    $this->actingAs(adminCatalogApiUser())
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/products/{$product->getKey()}")
        ->assertOk()
        ->assertJsonPath('data.title', 'Admin API Jacket')
        ->assertJsonPath('data.variants.0.price_amount', 3299)
        ->assertJsonPath('data.variants.0.inventory.quantity_on_hand', 50);
});

test('admin product api creates updates and archives products', function (): void {
    $store = adminCatalogApiStore();
    $collection = ProductCollection::factory()->create([
        'store_id' => $store->getKey(),
        'title' => 'API Summer',
        'handle' => 'api-summer',
    ]);
    $user = adminCatalogApiUser();

    $createResponse = $this->actingAs($user)
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/products", [
            'title' => 'API Cotton T-Shirt',
            'handle' => 'api-cotton-t-shirt',
            'description_html' => '<p>Soft cotton tee.</p>',
            'vendor' => 'API Apparel',
            'product_type' => 'Shirts',
            'status' => 'active',
            'tags' => ['organic', 'cotton'],
            'options' => [
                ['name' => 'Color', 'position' => 1],
                ['name' => 'Size', 'position' => 2],
            ],
            'variants' => [
                [
                    'sku' => 'API-TEE-BLU-S',
                    'price_amount' => 2500,
                    'compare_at_amount' => 3000,
                    'is_default' => true,
                    'position' => 1,
                    'option_values' => [
                        ['option_name' => 'Color', 'value' => 'Blue'],
                        ['option_name' => 'Size', 'value' => 'Small'],
                    ],
                    'inventory' => [
                        'quantity_on_hand' => 12,
                        'policy' => 'deny',
                    ],
                ],
                [
                    'sku' => 'API-TEE-BLU-M',
                    'price_amount' => 2600,
                    'is_default' => false,
                    'position' => 2,
                    'option_values' => [
                        ['option_name' => 'Color', 'value' => 'Blue'],
                        ['option_name' => 'Size', 'value' => 'Medium'],
                    ],
                    'inventory' => [
                        'quantity_on_hand' => 8,
                        'policy' => 'continue',
                    ],
                ],
            ],
            'collections' => [$collection->getKey()],
        ])
        ->assertCreated()
        ->assertJsonPath('data.title', 'API Cotton T-Shirt')
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.variants.0.inventory.quantity_on_hand', 12)
        ->assertJsonPath('data.collections.0.handle', 'api-summer');

    $product = Product::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('handle', 'api-cotton-t-shirt')
        ->firstOrFail();
    $defaultVariant = ProductVariant::withoutGlobalScopes()
        ->where('product_id', $product->getKey())
        ->where('is_default', true)
        ->firstOrFail();
    $removedVariant = ProductVariant::withoutGlobalScopes()
        ->where('product_id', $product->getKey())
        ->where('sku', 'API-TEE-BLU-M')
        ->firstOrFail();

    expect($product->status)->toBe(ProductStatus::Active)
        ->and($product->options()->count())->toBe(2)
        ->and($product->variants()->count())->toBe(2);

    $this->actingAs($user)
        ->putJson("/api/admin/v1/stores/{$store->getKey()}/products/{$product->getKey()}", [
            'title' => 'API Cotton T-Shirt Updated',
            'tags' => ['organic', 'bestseller'],
            'variants' => [
                [
                    'id' => $defaultVariant->getKey(),
                    'sku' => 'API-TEE-BLU-S-UPDATED',
                    'price_amount' => 2700,
                    'is_default' => true,
                    'position' => 1,
                    'option_values' => [
                        ['option_name' => 'Color', 'value' => 'Blue'],
                        ['option_name' => 'Size', 'value' => 'Small'],
                    ],
                    'inventory' => [
                        'quantity_on_hand' => 15,
                    ],
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('data.title', 'API Cotton T-Shirt Updated')
        ->assertJsonPath('data.variants.0.sku', 'API-TEE-BLU-S-UPDATED')
        ->assertJsonPath('data.variants.0.inventory.quantity_on_hand', 15);

    $this->assertModelMissing($removedVariant);

    $this->actingAs($user)
        ->deleteJson("/api/admin/v1/stores/{$store->getKey()}/products/{$product->getKey()}")
        ->assertOk()
        ->assertJsonPath('data.status', 'archived');

    expect($product->refresh()->status)->toBe(ProductStatus::Archived);
});

test('admin product api presigns media uploads', function (): void {
    $store = adminCatalogApiStore();
    $product = Product::factory()->withDefaultVariant()->create([
        'store_id' => $store->getKey(),
        'title' => 'API Media Product',
    ]);

    $this->actingAs(adminCatalogApiUser())
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/products/{$product->getKey()}/media/presign-upload", [
            'filename' => 'front.jpg',
            'content_type' => 'image/jpeg',
            'byte_size' => 245000,
        ])
        ->assertCreated()
        ->assertJsonPath('method', 'PUT')
        ->assertJsonPath('headers.Content-Type', 'image/jpeg')
        ->assertJsonPath('storage_key', fn (string $storageKey): bool => str_starts_with($storageKey, "media/originals/{$product->getKey()}/"))
        ->assertJsonPath('upload_url', fn (string $uploadUrl): bool => $uploadUrl !== '');

    $media = ProductMedia::withoutGlobalScopes()
        ->where('product_id', $product->getKey())
        ->firstOrFail();

    expect($media->status)->toBe(MediaStatus::Processing)
        ->and($media->mime_type)->toBe('image/jpeg')
        ->and($media->byte_size)->toBe(245000)
        ->and($media->position)->toBe(0);
});

test('admin customer api lists and shows store scoped customers', function (): void {
    $store = adminCatalogApiStore();
    $customer = Customer::factory()->create([
        'store_id' => $store->getKey(),
        'email' => 'customer-api@example.test',
        'name' => 'Customer API',
        'marketing_opt_in' => true,
    ]);
    CustomerAddress::factory()->default()->create(['customer_id' => $customer->getKey()]);
    Order::factory()->paid()->forCustomer($customer)->create([
        'store_id' => $store->getKey(),
        'order_number' => '#CA-1001',
        'total_amount' => 4400,
    ]);
    Customer::factory()->create([
        'store_id' => Store::factory()->create()->getKey(),
        'email' => 'other-customer@example.test',
    ]);

    $this->actingAs(adminCatalogApiUser())
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/customers?query=customer-api")
        ->assertOk()
        ->assertJsonPath('data.0.email', 'customer-api@example.test')
        ->assertJsonPath('data.0.orders_count', 1)
        ->assertJsonPath('data.0.total_spent_amount', 4400)
        ->assertJsonMissing(['email' => 'other-customer@example.test']);

    $this->actingAs(adminCatalogApiUser())
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/customers/{$customer->getKey()}")
        ->assertOk()
        ->assertJsonPath('data.email', 'customer-api@example.test')
        ->assertJsonPath('data.addresses.0.is_default', true)
        ->assertJsonPath('data.orders.0.order_number', '#CA-1001');
});

test('admin catalog api accepts scoped tokens and enforces abilities', function (): void {
    $store = adminCatalogApiStore();
    Product::factory()->withDefaultVariant()->create([
        'store_id' => $store->getKey(),
        'title' => 'Token Visible Product',
    ]);
    Customer::factory()->create([
        'store_id' => $store->getKey(),
        'email' => 'token-customer@example.test',
    ]);
    $productToken = adminCatalogApiToken($store, ['read-products']);
    $writeProductToken = adminCatalogApiToken($store, ['write-products']);
    $customerToken = adminCatalogApiToken($store, ['read-customers']);

    $this->withToken($productToken['plain_text'])
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/products?query=Token Visible")
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Token Visible Product');

    $this->withToken($productToken['plain_text'])
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/customers")
        ->assertForbidden();

    $this->withToken($productToken['plain_text'])
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/products", [
            'title' => 'Token Created Product',
            'variants' => [
                [
                    'sku' => 'TOKEN-CREATED-001',
                    'price_amount' => 1999,
                    'is_default' => true,
                ],
            ],
        ])
        ->assertForbidden();

    $this->withToken($writeProductToken['plain_text'])
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/products", [
            'title' => 'Token Created Product',
            'variants' => [
                [
                    'sku' => 'TOKEN-CREATED-001',
                    'price_amount' => 1999,
                    'is_default' => true,
                ],
            ],
        ])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Token Created Product');

    $this->withToken($customerToken['plain_text'])
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/customers?query=token-customer")
        ->assertOk()
        ->assertJsonPath('data.0.email', 'token-customer@example.test');

    expect($productToken['token']->refresh()->last_used_at)->not->toBeNull()
        ->and($writeProductToken['token']->refresh()->last_used_at)->not->toBeNull()
        ->and($customerToken['token']->refresh()->last_used_at)->not->toBeNull();
});

test('admin catalog api rejects resources outside the requested store', function (): void {
    $store = adminCatalogApiStore();
    $otherStore = Store::factory()->create();
    $otherProduct = Product::factory()->withDefaultVariant()->create(['store_id' => $otherStore->getKey()]);
    $otherCustomer = Customer::factory()->create(['store_id' => $otherStore->getKey()]);

    $this->actingAs(adminCatalogApiUser())
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/products/{$otherProduct->getKey()}")
        ->assertNotFound();

    $this->actingAs(adminCatalogApiUser())
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/customers/{$otherCustomer->getKey()}")
        ->assertNotFound();
});

test('deferred oauth and app ecosystem routes return not implemented', function (): void {
    $store = adminCatalogApiStore();

    $this->actingAs(adminCatalogApiUser())
        ->getJson('/oauth/authorize?client_id=test&redirect_uri=https://example.test/callback&response_type=code&scope=read-products&state=state')
        ->assertStatus(501)
        ->assertJsonPath('message', 'OAuth app ecosystem endpoints are deferred for initial implementation.');

    $this->postJson('/oauth/token', [
        'grant_type' => 'authorization_code',
        'client_id' => 'test',
        'client_secret' => 'secret',
        'redirect_uri' => 'https://example.test/callback',
        'code' => 'code',
    ])
        ->assertStatus(501)
        ->assertJsonPath('message', 'OAuth app ecosystem endpoints are deferred for initial implementation.');

    $this->getJson("/api/apps/v1/stores/{$store->getKey()}/products")
        ->assertStatus(501)
        ->assertJsonPath('message', 'App API endpoints are deferred for initial implementation.');
});
