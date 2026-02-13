<?php

use App\Enums\NavigationItemType;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Services\NavigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    StoreDomain::factory()->create([
        'store_id' => $this->store->id,
        'hostname' => 'test-store.test',
        'type' => 'storefront',
        'is_primary' => true,
    ]);
    app()->instance('current_store', $this->store);
    $this->service = new NavigationService;
});

test('buildTree returns array of nav items', function () {
    $menu = NavigationMenu::factory()->create(['store_id' => $this->store->id]);
    NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'type' => NavigationItemType::Link,
        'label' => 'Home',
        'url' => '/',
        'position' => 0,
    ]);
    NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'type' => NavigationItemType::Link,
        'label' => 'Shop',
        'url' => '/collections',
        'position' => 1,
    ]);

    $menu->load('items');
    $tree = $this->service->buildTree($menu);

    expect($tree)->toHaveCount(2)
        ->and($tree[0]['label'])->toBe('Home')
        ->and($tree[0]['url'])->toBe('/')
        ->and($tree[1]['label'])->toBe('Shop')
        ->and($tree[1]['url'])->toBe('/collections');
});

test('resolveUrl returns direct url for link type', function () {
    $menu = NavigationMenu::factory()->create(['store_id' => $this->store->id]);
    $item = NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'type' => NavigationItemType::Link,
        'url' => '/test-url',
    ]);

    $url = $this->service->resolveUrl($item);

    expect($url)->toBe('/test-url');
});

test('resolveUrl returns page url for page type', function () {
    $page = Page::factory()->published()->create([
        'store_id' => $this->store->id,
        'handle' => 'about',
    ]);

    $menu = NavigationMenu::factory()->create(['store_id' => $this->store->id]);
    $item = NavigationItem::factory()->forPage($page->id)->create([
        'menu_id' => $menu->id,
        'label' => 'About',
    ]);

    $url = $this->service->resolveUrl($item);

    expect($url)->toContain('/pages/about');
});

test('resolveUrl returns fallback for missing resource', function () {
    $menu = NavigationMenu::factory()->create(['store_id' => $this->store->id]);
    $item = NavigationItem::factory()->forPage(99999)->create([
        'menu_id' => $menu->id,
        'label' => 'Missing',
    ]);

    $url = $this->service->resolveUrl($item);

    expect($url)->toBe('#');
});

test('buildTree caches results', function () {
    $menu = NavigationMenu::factory()->create(['store_id' => $this->store->id]);
    NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'label' => 'Home',
        'url' => '/',
        'position' => 0,
    ]);

    $menu->load('items');

    Cache::shouldReceive('remember')
        ->once()
        ->andReturn([['id' => 1, 'label' => 'Home', 'url' => '/', 'type' => 'link', 'position' => 0]]);

    $tree = $this->service->buildTree($menu);

    expect($tree)->toHaveCount(1);
});
