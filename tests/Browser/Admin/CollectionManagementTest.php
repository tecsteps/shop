<?php

use App\Models\Collection;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Browser\Playwright\Playwright;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Playwright::setHost('shop.test');

    $this->seed(DatabaseSeeder::class);
});

afterEach(function (): void {
    Playwright::setHost(null);
});

function adminCollectionBrowserHost(): array
{
    return ['host' => 'shop.test'];
}

function adminCollectionBrowserStore(): Store
{
    return Store::query()->where('handle', 'acme-fashion')->firstOrFail();
}

function adminCollectionBrowserAuthenticate(mixed $testCase): Store
{
    $store = adminCollectionBrowserStore();
    $user = User::query()->where('email', 'admin@acme.test')->firstOrFail();

    $testCase->actingAs($user);
    $testCase->withSession(['current_store_id' => $store->getKey()]);

    return $store;
}

function adminCollectionOpenCollections(mixed $testCase): mixed
{
    adminCollectionBrowserAuthenticate($testCase);

    return visit('/admin/collections', adminCollectionBrowserHost())
        ->wait(1)
        ->assertPathIs('/admin/collections')
        ->assertSee('Collections')
        ->assertNoJavaScriptErrors();
}

function adminCollectionSave(mixed $page): mixed
{
    return $page
        ->click('button[data-test="collection-save-button"]')
        ->wait(1)
        ->assertSee('Collection saved')
        ->assertNoJavaScriptErrors();
}

test('shows the collection list with seeded collections', function (): void {
    adminCollectionOpenCollections($this)
        ->assertSee('T-Shirts')
        ->assertSee('New Arrivals')
        ->assertNoJavaScriptErrors();
});

test('can create a new collection', function (): void {
    $store = adminCollectionBrowserAuthenticate($this);
    $page = adminCollectionOpenCollections($this)
        ->click('a[href$="/admin/collections/create"]')
        ->wait(1)
        ->assertPathIs('/admin/collections/create')
        ->assertSee('Add collection')
        ->assertNoJavaScriptErrors();

    $page
        ->fill('input[wire\\:model\\.live\\.debounce\\.300ms="title"]', 'E2E Test Collection')
        ->fill('input[wire\\:model="handle"]', 'e2e-test-collection')
        ->fill('textarea[wire\\:model="descriptionHtml"]', 'A collection created by the E2E test suite.');

    adminCollectionSave($page)
        ->navigate('/admin/collections')
        ->wait(1)
        ->assertPathIs('/admin/collections')
        ->assertSee('E2E Test Collection')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseHas('collections', [
        'store_id' => $store->getKey(),
        'title' => 'E2E Test Collection',
        'handle' => 'e2e-test-collection',
        'description_html' => 'A collection created by the E2E test suite.',
    ]);
});

test('can edit a collection', function (): void {
    $store = adminCollectionBrowserAuthenticate($this);

    $page = adminCollectionOpenCollections($this)
        ->click('a:has-text("T-Shirts")')
        ->wait(1)
        ->assertPathContains('/admin/collections/')
        ->assertValue('input[wire\\:model\\.live\\.debounce\\.300ms="title"]', 'T-Shirts')
        ->assertNoJavaScriptErrors();

    $page->fill('textarea[wire\\:model="descriptionHtml"]', 'Updated description for T-Shirts collection.');

    adminCollectionSave($page);

    $collection = Collection::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('title', 'T-Shirts')
        ->firstOrFail();

    expect($collection->description_html)->toBe('Updated description for T-Shirts collection.');
});
