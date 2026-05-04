<?php

use App\Models\Product;
use App\Models\SearchSettings;
use App\Models\Store;
use App\Models\User;
use App\Services\SearchService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function adminSearchIndexApiStore(): Store
{
    return Store::query()->where('handle', 'acme-fashion')->firstOrFail();
}

function adminSearchIndexApiUser(): User
{
    return User::query()->where('email', 'admin@acme.test')->firstOrFail();
}

/**
 * @param  list<string>  $abilities
 * @return array{token: \App\Models\PersonalAccessToken, plain_text: string}
 */
function adminSearchIndexApiToken(Store $store, array $abilities): array
{
    return adminApiToken($store, $abilities);
}

test('admin search index api reports status and rebuilds stale documents', function (): void {
    $store = adminSearchIndexApiStore();
    $product = Product::factory()
        ->for($store)
        ->withDefaultVariant(2999)
        ->create([
            'title' => 'API Reindex Linen Jacket',
            'handle' => 'api-reindex-linen-jacket',
        ]);

    DB::table('products_fts')->where('product_id', $product->getKey())->delete();

    expect(app(SearchService::class)->search($store, 'api reindex linen', [], 12)->total())->toBe(0);

    $this->withToken(adminApiBearerToken($store, ['read-settings'], adminSearchIndexApiUser()))
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/search/status")
        ->assertOk()
        ->assertJsonPath('data.index_status', 'stale')
        ->assertJsonPath('data.pending_updates', 1);

    $this->withToken(adminApiBearerToken($store, ['write-settings'], adminSearchIndexApiUser()))
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/search/reindex")
        ->assertAccepted()
        ->assertJsonPath('status', 'completed')
        ->assertJsonPath('documents_count', Product::withoutGlobalScopes()->where('store_id', $store->getKey())->count());

    expect(app(SearchService::class)->search($store, 'api reindex linen', [], 12)->total())->toBe(1)
        ->and(SearchSettings::withoutGlobalScopes()->where('store_id', $store->getKey())->firstOrFail()->updated_at)->not->toBeNull();

    $this->withToken(adminApiBearerToken($store, ['read-settings'], adminSearchIndexApiUser()))
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/search/status")
        ->assertOk()
        ->assertJsonPath('data.index_status', 'ready')
        ->assertJsonPath('data.pending_updates', 0);
});

test('admin search index api enforces token abilities and store scope', function (): void {
    $store = adminSearchIndexApiStore();
    $otherStore = Store::factory()->create();
    $readToken = adminSearchIndexApiToken($store, ['read-settings']);
    $writeToken = adminSearchIndexApiToken($store, ['write-settings']);
    $otherStoreToken = adminSearchIndexApiToken($otherStore, ['read-settings']);

    $this->withToken($readToken['plain_text'])
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/search/status")
        ->assertOk();

    $this->withToken($readToken['plain_text'])
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/search/reindex")
        ->assertForbidden();

    $this->withToken($otherStoreToken['plain_text'])
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/search/status")
        ->assertForbidden();

    $this->withToken($writeToken['plain_text'])
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/search/reindex")
        ->assertAccepted();
});
