<?php

use App\Models\Collection;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Services\NavigationService;
use App\Services\ThemeService;
use Tests\Support\CommerceTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('builds an ordered menu tree with resolved urls', function () {
    ['store' => $store] = $this->createStoreContext();
    $menu = NavigationMenu::factory()->create(['store_id' => $store->id, 'handle' => 'main-menu']);
    $page = Page::factory()->create(['store_id' => $store->id, 'handle' => 'about-us', 'status' => 'published']);
    $collection = Collection::factory()->create(['store_id' => $store->id, 'handle' => 'summer', 'status' => 'active']);
    $variant = CommerceTestHelper::purchasableVariant($store);

    NavigationItem::factory()->create(['menu_id' => $menu->id, 'type' => 'collection', 'label' => 'Summer', 'resource_id' => $collection->id, 'position' => 2]);
    NavigationItem::factory()->create(['menu_id' => $menu->id, 'type' => 'link', 'label' => 'External', 'url' => 'https://example.com', 'position' => 3]);
    NavigationItem::factory()->create(['menu_id' => $menu->id, 'type' => 'page', 'label' => 'About', 'resource_id' => $page->id, 'position' => 1]);
    NavigationItem::factory()->create(['menu_id' => $menu->id, 'type' => 'product', 'label' => 'Featured', 'resource_id' => $variant->product_id, 'position' => 0]);

    $tree = app(NavigationService::class)->buildTree($menu->refresh());

    expect($tree)->toHaveCount(4);
    expect(array_column($tree, 'label'))->toBe(['Featured', 'About', 'Summer', 'External']);
    expect($tree[0]['url'])->toContain('/products/'.$variant->product->handle);
    expect($tree[1]['url'])->toContain('/pages/about-us');
    expect($tree[2]['url'])->toContain('/collections/summer');
    expect($tree[3]['url'])->toBe('https://example.com');
});

it('falls back to a placeholder url for missing resources', function () {
    ['store' => $store] = $this->createStoreContext();
    $menu = NavigationMenu::factory()->create(['store_id' => $store->id, 'handle' => 'main-menu']);
    $item = NavigationItem::factory()->create(['menu_id' => $menu->id, 'type' => 'page', 'resource_id' => 999999]);

    expect(app(NavigationService::class)->resolveUrl($item))->toBe('#');
});

it('renders header and footer navigation on storefront pages', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $menu = NavigationMenu::factory()->create(['store_id' => $store->id, 'handle' => 'main-menu']);
    NavigationItem::factory()->create(['menu_id' => $menu->id, 'type' => 'link', 'label' => 'Header Link Label', 'url' => '/collections']);
    $footer = NavigationMenu::factory()->create(['store_id' => $store->id, 'handle' => 'footer-menu']);
    NavigationItem::factory()->create(['menu_id' => $footer->id, 'type' => 'link', 'label' => 'Footer Link Label', 'url' => '/cart']);

    $response = $this->get(storefrontUrl($domain->hostname, '/'));

    $response->assertOk();
    $response->assertSee('Header Link Label');
    $response->assertSee('Footer Link Label');
});

it('resolves the active theme and its settings', function () {
    ['store' => $store] = $this->createStoreContext();
    $theme = App\Models\Theme::factory()->create(['store_id' => $store->id, 'status' => 'published', 'published_at' => now()]);
    App\Models\ThemeSetting::factory()->create([
        'theme_id' => $theme->id,
        'settings_json' => ['announcement' => ['show' => true, 'text' => 'Big Sale Today']],
    ]);

    $service = app(ThemeService::class);

    expect($service->activeTheme($store)?->id)->toBe($theme->id);

    $settings = $service->settings($store);

    expect($settings['announcement']['text'])->toBe('Big Sale Today');
    expect($settings['hero']['heading'])->toContain($store->name);
});

it('renders the announcement bar when enabled in theme settings', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $theme = App\Models\Theme::factory()->create(['store_id' => $store->id, 'status' => 'published', 'published_at' => now()]);
    App\Models\ThemeSetting::factory()->create([
        'theme_id' => $theme->id,
        'settings_json' => ['announcement' => ['show' => true, 'text' => 'Storewide Announcement Text']],
    ]);

    $response = $this->get(storefrontUrl($domain->hostname, '/'));

    $response->assertOk();
    $response->assertSee('Storewide Announcement Text');
});
