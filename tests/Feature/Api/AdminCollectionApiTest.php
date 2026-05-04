<?php

use App\Models\Collection;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function adminCollectionApiStore(): Store
{
    return Store::query()->where('handle', 'acme-fashion')->firstOrFail();
}

function adminCollectionApiUser(): User
{
    return User::query()->where('email', 'admin@acme.test')->firstOrFail();
}

/**
 * @param  list<string>  $abilities
 * @return array{token: \App\Models\PersonalAccessToken, plain_text: string}
 */
function adminCollectionApiToken(Store $store, array $abilities): array
{
    return adminApiToken($store, $abilities);
}

test('admin collection api lists creates updates and deletes collections', function (): void {
    $store = adminCollectionApiStore();
    $products = Product::factory()
        ->count(3)
        ->withDefaultVariant()
        ->create(['store_id' => $store->getKey()]);
    Collection::factory()->create([
        'store_id' => Store::factory()->create()->getKey(),
        'title' => 'Other Store Collection',
    ]);
    $user = adminCollectionApiUser();
    $readToken = adminApiBearerToken($store, ['read-collections'], $user);
    $writeToken = adminApiBearerToken($store, ['write-collections'], $user);

    $this->withToken($readToken)
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/collections?query=New")
        ->assertOk()
        ->assertJsonMissing(['title' => 'Other Store Collection']);

    $createResponse = $this->withToken($writeToken)
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/collections", [
            'title' => 'API Winter',
            'description_html' => '<p>Cold weather goods.</p>',
            'type' => 'manual',
            'status' => 'active',
            'product_ids' => $products->take(2)->pluck('id')->all(),
        ])
        ->assertCreated()
        ->assertJsonPath('data.title', 'API Winter')
        ->assertJsonPath('data.handle', 'api-winter')
        ->assertJsonPath('data.products_count', 2);

    $collectionId = $createResponse->json('data.id');
    $collection = Collection::withoutGlobalScopes()->findOrFail($collectionId);

    expect($collection->products()->pluck('collection_products.position', 'products.id')->all())
        ->toBe([
            $products[0]->getKey() => 0,
            $products[1]->getKey() => 1,
        ]);

    $this->withToken($writeToken)
        ->putJson("/api/admin/v1/stores/{$store->getKey()}/collections/{$collection->getKey()}", [
            'title' => 'API Winter Edit',
            'status' => 'draft',
            'add_product_ids' => [$products[2]->getKey()],
            'remove_product_ids' => [$products[0]->getKey()],
        ])
        ->assertOk()
        ->assertJsonPath('data.title', 'API Winter Edit')
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.products_count', 2);

    expect($collection->refresh()->products()->pluck('products.id')->all())
        ->toBe([$products[1]->getKey(), $products[2]->getKey()]);

    $this->withToken($writeToken)
        ->deleteJson("/api/admin/v1/stores/{$store->getKey()}/collections/{$collection->getKey()}")
        ->assertOk()
        ->assertJsonPath('message', 'Collection deleted');

    expect(Collection::withoutGlobalScopes()->whereKey($collection->getKey())->exists())->toBeFalse();
});

test('admin collection api enforces token abilities and store scope', function (): void {
    $store = adminCollectionApiStore();
    $otherStore = Store::factory()->create();
    $collection = Collection::factory()->create([
        'store_id' => $store->getKey(),
        'title' => 'Token Collection',
    ]);
    $readToken = adminCollectionApiToken($store, ['read-collections']);
    $writeToken = adminCollectionApiToken($store, ['write-collections']);
    $otherStoreToken = adminCollectionApiToken($otherStore, ['read-collections']);

    $this->withToken($readToken['plain_text'])
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/collections?query=Token")
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Token Collection');

    $this->withToken($readToken['plain_text'])
        ->deleteJson("/api/admin/v1/stores/{$store->getKey()}/collections/{$collection->getKey()}")
        ->assertForbidden();

    $this->withToken($otherStoreToken['plain_text'])
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/collections")
        ->assertForbidden();

    $this->withToken($writeToken['plain_text'])
        ->deleteJson("/api/admin/v1/stores/{$store->getKey()}/collections/{$collection->getKey()}")
        ->assertOk();
});

test('admin collection api validates handles and product store ownership', function (): void {
    $store = adminCollectionApiStore();
    $existing = Collection::factory()->create([
        'store_id' => $store->getKey(),
        'handle' => 'existing-api-collection',
    ]);
    $otherStoreProduct = Product::factory()
        ->withDefaultVariant()
        ->create(['store_id' => Store::factory()->create()->getKey()]);

    $this->withToken(adminApiBearerToken($store, ['write-collections'], adminCollectionApiUser()))
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/collections", [
            'title' => 'Invalid API Collection',
            'handle' => $existing->handle,
            'type' => 'manual',
            'product_ids' => [$otherStoreProduct->getKey()],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['handle', 'product_ids.0']);
});
