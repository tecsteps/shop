<?php

use App\Models\Page;
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

function adminPageApiStore(): Store
{
    return Store::query()->where('handle', 'acme-fashion')->firstOrFail();
}

function adminPageApiUser(): User
{
    return User::query()->where('email', 'admin@acme.test')->firstOrFail();
}

/**
 * @param  list<string>  $abilities
 * @return array{token: \App\Models\OauthToken, plain_text: string}
 */
function adminPageApiToken(Store $store, array $abilities): array
{
    return app(WebhookService::class)->createApiToken($store, 'Content integration', $abilities);
}

test('admin page api lists creates updates and deletes pages', function (): void {
    $store = adminPageApiStore();
    $otherStore = Store::factory()->create();
    Page::factory()->published()->create([
        'store_id' => $store->getKey(),
        'title' => 'API Visible Page',
        'handle' => 'api-visible-page',
    ]);
    Page::factory()->published()->create([
        'store_id' => $otherStore->getKey(),
        'title' => 'Other Store Page',
        'handle' => 'other-store-page',
    ]);
    $user = adminPageApiUser();

    $this->actingAs($user)
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/pages?status=published&query=API")
        ->assertOk()
        ->assertJsonFragment(['title' => 'API Visible Page'])
        ->assertJsonMissing(['title' => 'Other Store Page']);

    $createResponse = $this->actingAs($user)
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/pages", [
            'title' => 'Shipping Policy API',
            'body_html' => '<h2>Shipping Policy</h2><p>We ship worldwide.</p>',
            'status' => 'published',
        ])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Shipping Policy API')
        ->assertJsonPath('data.handle', 'shipping-policy-api')
        ->assertJsonPath('data.status', 'published');

    $page = Page::withoutGlobalScopes()->findOrFail($createResponse->json('data.id'));

    expect($page->published_at)->not->toBeNull();

    $this->actingAs($user)
        ->putJson("/api/admin/v1/stores/{$store->getKey()}/pages/{$page->getKey()}", [
            'handle' => 'API Shipping Policy Updated',
            'body_html' => '<p>Updated policy.</p>',
            'status' => 'draft',
        ])
        ->assertOk()
        ->assertJsonPath('data.handle', 'api-shipping-policy-updated')
        ->assertJsonPath('data.body_html', '<p>Updated policy.</p>')
        ->assertJsonPath('data.status', 'draft');

    $this->actingAs($user)
        ->deleteJson("/api/admin/v1/stores/{$store->getKey()}/pages/{$page->getKey()}")
        ->assertOk()
        ->assertJsonPath('message', 'Page deleted');

    expect(Page::withoutGlobalScopes()->whereKey($page->getKey())->exists())->toBeFalse();
});

test('admin page api enforces token abilities and store scope', function (): void {
    $store = adminPageApiStore();
    $otherStore = Store::factory()->create();
    $page = Page::factory()->published()->create([
        'store_id' => $store->getKey(),
        'title' => 'Token Content Page',
        'handle' => 'token-content-page',
    ]);
    $readToken = adminPageApiToken($store, ['read-content']);
    $writeToken = adminPageApiToken($store, ['write-content']);
    $otherStoreToken = adminPageApiToken($otherStore, ['read-content']);

    $this->withToken($readToken['plain_text'])
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/pages?query=Token")
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Token Content Page');

    $this->withToken($readToken['plain_text'])
        ->deleteJson("/api/admin/v1/stores/{$store->getKey()}/pages/{$page->getKey()}")
        ->assertForbidden();

    $this->withToken($otherStoreToken['plain_text'])
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/pages")
        ->assertForbidden();

    $this->withToken($writeToken['plain_text'])
        ->deleteJson("/api/admin/v1/stores/{$store->getKey()}/pages/{$page->getKey()}")
        ->assertOk();
});

test('admin page api validates normalized handles and published dates', function (): void {
    $store = adminPageApiStore();
    $existing = Page::factory()->create([
        'store_id' => $store->getKey(),
        'handle' => 'existing-api-page',
    ]);

    $this->actingAs(adminPageApiUser())
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/pages", [
            'title' => 'Invalid API Page',
            'handle' => str_replace('-', ' ', $existing->handle),
            'status' => 'published',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['handle']);

    $this->actingAs(adminPageApiUser())
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/pages", [
            'title' => 'Invalid API Date',
            'status' => 'published',
            'published_at' => 'not-a-date',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['published_at']);
});
