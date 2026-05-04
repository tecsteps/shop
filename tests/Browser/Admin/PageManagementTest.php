<?php

use App\Models\Page;
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

function adminPageBrowserHost(): array
{
    return ['host' => 'shop.test'];
}

function adminPageBrowserAuthenticate(mixed $testCase): Store
{
    $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $user = User::query()->where('email', 'admin@acme.test')->firstOrFail();

    $testCase->actingAs($user);
    $testCase->withSession(['current_store_id' => $store->getKey()]);

    return $store;
}

function adminPageBrowserOpenPages(mixed $testCase): mixed
{
    adminPageBrowserAuthenticate($testCase);

    return visit('/admin/pages', adminPageBrowserHost())
        ->wait(1)
        ->assertPathIs('/admin/pages')
        ->assertSee('Pages')
        ->assertNoJavaScriptErrors();
}

function adminPageBrowserSave(mixed $page): mixed
{
    return $page
        ->click('button[data-test="page-save-button"]')
        ->wait(1)
        ->assertSee('Page saved')
        ->assertNoJavaScriptErrors();
}

test('shows the pages list', function (): void {
    adminPageBrowserOpenPages($this)
        ->assertSee('About')
        ->assertNoJavaScriptErrors();
});

test('can create a new page', function (): void {
    $store = adminPageBrowserAuthenticate($this);
    $page = adminPageBrowserOpenPages($this)
        ->click('a[href$="/admin/pages/create"]')
        ->wait(1)
        ->assertPathIs('/admin/pages/create')
        ->assertSee('Create page')
        ->assertNoJavaScriptErrors();

    $page
        ->fill('input[wire\\:model\\.live\\.debounce\\.300ms="title"]', 'FAQ')
        ->fill('input[wire\\:model="handle"]', 'faq-browser')
        ->fill('textarea[wire\\:model="bodyHtml"]', 'Frequently asked questions content here.');

    adminPageBrowserSave($page);

    $this->assertDatabaseHas('pages', [
        'store_id' => $store->getKey(),
        'title' => 'FAQ',
        'handle' => 'faq-browser',
        'body_html' => 'Frequently asked questions content here.',
    ]);
});

test('can edit an existing page', function (): void {
    $store = adminPageBrowserAuthenticate($this);
    $about = Page::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('handle', 'about')
        ->firstOrFail();

    $page = visit('/admin/pages', adminPageBrowserHost())
        ->wait(1)
        ->assertPathIs('/admin/pages')
        ->click('a:has-text("About")')
        ->wait(1)
        ->assertPathIs('/admin/pages/'.$about->getKey().'/edit')
        ->assertSee('Edit page')
        ->assertNoJavaScriptErrors();

    $page->fill('textarea[wire\\:model="bodyHtml"]', 'Updated about page content.');

    adminPageBrowserSave($page);

    expect($about->refresh()->body_html)->toBe('Updated about page content.');
});
