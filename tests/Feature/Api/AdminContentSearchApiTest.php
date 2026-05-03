<?php

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\ApiTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
    $this->seed();
    $this->store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $this->otherStore = Store::query()->where('handle', 'acme-electronics')->firstOrFail();
    $this->user = User::query()->where('email', 'admin@example.com')->firstOrFail();
});

function adminContentSearchTokenFor($test, array $abilities): string
{
    return app(ApiTokenService::class)->create($test->store, $test->user, 'Content and search API test', $abilities)['plain_text_token'];
}

test('admin pages api lists creates updates and deletes pages', function (): void {
    $indexUrl = route('api.admin.pages.index', $this->store);

    $this->getJson($indexUrl)->assertUnauthorized();

    $this->withToken(adminContentSearchTokenFor($this, ['write-content']))
        ->getJson($indexUrl)
        ->assertForbidden();

    $this->withToken(adminContentSearchTokenFor($this, ['read-content']))
        ->getJson($indexUrl.'?status=published&per_page=5')
        ->assertOk()
        ->assertJsonPath('data.0.handle', 'about')
        ->assertJsonPath('data.0.store_id', $this->store->id)
        ->assertJsonPath('meta.per_page', 5);

    $response = $this->withToken(adminContentSearchTokenFor($this, ['write-content']))
        ->postJson(route('api.admin.pages.store', $this->store), [
            'title' => 'API Shipping Policy',
            'body_html' => '<h2>Shipping</h2><p>Worldwide<script>alert(1)</script></p>',
            'status' => PageStatus::Published->value,
        ])
        ->assertCreated()
        ->assertJsonPath('data.handle', 'api-shipping-policy')
        ->assertJsonPath('data.status', PageStatus::Published->value)
        ->assertJsonPath('data.body_html', '<h2>Shipping</h2><p>Worldwide</p>');

    $page = Page::query()->whereKey($response->json('data.id'))->firstOrFail();

    expect($page->published_at)->not->toBeNull()
        ->and($page->body_html)->toBe('<h2>Shipping</h2><p>Worldwide</p>');

    $this->withToken(adminContentSearchTokenFor($this, ['write-content']))
        ->putJson(route('api.admin.pages.update', [$this->store, $page]), [
            'title' => 'API Shipping Updated',
            'handle' => null,
            'body_html' => '<p>Updated<script>alert(1)</script></p>',
            'status' => PageStatus::Draft->value,
        ])
        ->assertOk()
        ->assertJsonPath('data.title', 'API Shipping Updated')
        ->assertJsonPath('data.handle', 'api-shipping-updated')
        ->assertJsonPath('data.status', PageStatus::Draft->value)
        ->assertJsonPath('data.published_at', null)
        ->assertJsonPath('data.body_html', '<p>Updated</p>');

    $page->refresh();

    expect($page->published_at)->toBeNull()
        ->and($page->body_html)->toBe('<p>Updated</p>');

    $this->withToken(adminContentSearchTokenFor($this, ['write-content']))
        ->deleteJson(route('api.admin.pages.destroy', [$this->store, $page]))
        ->assertOk()
        ->assertJsonPath('message', 'Page deleted');

    expect(Page::query()->whereKey($page->id)->exists())->toBeFalse();
});

test('admin pages api enforces store scoped token access', function (): void {
    $otherStorePage = Page::factory()
        ->for($this->otherStore)
        ->create(['handle' => 'electronics-policy']);

    $this->withToken(adminContentSearchTokenFor($this, ['read-content']))
        ->getJson(route('api.admin.pages.index', $this->otherStore))
        ->assertForbidden();

    $this->withToken(adminContentSearchTokenFor($this, ['write-content']))
        ->putJson(route('api.admin.pages.update', [$this->store, $otherStorePage]), [
            'title' => 'Cross tenant',
        ])
        ->assertNotFound();
});

test('admin search maintenance api reports status and reindexes store documents', function (): void {
    $statusUrl = route('api.admin.search.status', $this->store);
    $reindexUrl = route('api.admin.search.reindex', $this->store);
    $expectedDocuments = Product::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->count();

    $this->getJson($statusUrl)->assertUnauthorized();

    $this->withToken(adminContentSearchTokenFor($this, ['write-settings']))
        ->getJson($statusUrl)
        ->assertForbidden();

    $this->withToken(adminContentSearchTokenFor($this, ['read-settings']))
        ->getJson($statusUrl)
        ->assertOk()
        ->assertJsonPath('data.store_id', $this->store->id)
        ->assertJsonPath('data.index_status', 'ready')
        ->assertJsonPath('data.documents_count', $expectedDocuments)
        ->assertJsonPath('data.pending_updates', 0);

    DB::delete('DELETE FROM products_fts WHERE store_id = ?', [$this->store->id]);

    $this->withToken(adminContentSearchTokenFor($this, ['read-settings']))
        ->getJson($statusUrl)
        ->assertOk()
        ->assertJsonPath('data.index_status', 'pending')
        ->assertJsonPath('data.documents_count', 0)
        ->assertJsonPath('data.pending_updates', $expectedDocuments);

    $this->withToken(adminContentSearchTokenFor($this, ['read-settings']))
        ->postJson($reindexUrl)
        ->assertForbidden();

    $response = $this->withToken(adminContentSearchTokenFor($this, ['write-settings']))
        ->postJson($reindexUrl)
        ->assertAccepted()
        ->assertJsonPath('message', 'Reindex job queued.')
        ->assertJsonPath('status', 'queued')
        ->assertJsonPath('data.documents_count', $expectedDocuments);

    expect(Str::startsWith($response->json('job_id'), 'job_reindex_'))->toBeTrue();

    $this->withToken(adminContentSearchTokenFor($this, ['read-settings']))
        ->getJson($statusUrl)
        ->assertOk()
        ->assertJsonPath('data.index_status', 'ready')
        ->assertJsonPath('data.documents_count', $expectedDocuments)
        ->assertJsonPath('data.last_reindex_duration_seconds', 0)
        ->assertJsonPath('data.pending_updates', 0);
});
