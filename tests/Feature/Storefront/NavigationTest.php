<?php

use App\Enums\CollectionStatus;
use App\Enums\NavigationItemType;
use App\Enums\PageStatus;
use App\Models\Collection;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Product;
use App\Services\NavigationService;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ctx = createStoreContext();
});

it('builds correct navigation tree', function () {
    $menu = NavigationMenu::withoutGlobalScopes()->create([
        'store_id' => $this->ctx['store']->id,
        'name' => 'Main Menu',
        'handle' => 'main',
    ]);

    $parent = NavigationItem::create([
        'menu_id' => $menu->id,
        'title' => 'Shop',
        'type' => NavigationItemType::Link,
        'url' => '/shop',
        'position' => 0,
    ]);

    NavigationItem::create([
        'menu_id' => $menu->id,
        'title' => 'T-Shirts',
        'type' => NavigationItemType::Link,
        'url' => '/collections/t-shirts',
        'position' => 0,
        'parent_id' => $parent->id,
    ]);

    NavigationItem::create([
        'menu_id' => $menu->id,
        'title' => 'About',
        'type' => NavigationItemType::Link,
        'url' => '/pages/about',
        'position' => 1,
    ]);

    $service = app(NavigationService::class);

    // Clear cache to ensure fresh build
    cache()->forget("navigation:{$this->ctx['store']->id}:main");

    $tree = $service->buildTree($menu);

    expect($tree)->toHaveCount(2);
    expect($tree[0]['title'])->toBe('Shop');
    expect($tree[0]['url'])->toBe('/shop');
    expect($tree[0]['children'])->toHaveCount(1);
    expect($tree[0]['children'][0]['title'])->toBe('T-Shirts');
    expect($tree[1]['title'])->toBe('About');
    expect($tree[1]['children'])->toHaveCount(0);
});

it('resolves URLs for different item types', function () {
    $service = app(NavigationService::class);

    $menu = NavigationMenu::withoutGlobalScopes()->create([
        'store_id' => $this->ctx['store']->id,
        'name' => 'URL Test Menu',
        'handle' => 'url-test',
    ]);

    // Link type
    $linkItem = NavigationItem::create([
        'menu_id' => $menu->id,
        'title' => 'External',
        'type' => NavigationItemType::Link,
        'url' => 'https://example.com',
        'position' => 0,
    ]);
    expect($service->resolveUrl($linkItem))->toBe('https://example.com');

    // Page type
    $page = Page::withoutGlobalScopes()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'FAQ',
        'handle' => 'faq',
        'status' => PageStatus::Published,
        'published_at' => now(),
    ]);
    $pageItem = NavigationItem::create([
        'menu_id' => $menu->id,
        'title' => 'FAQ',
        'type' => NavigationItemType::Page,
        'resource_id' => $page->id,
        'position' => 1,
    ]);
    expect($service->resolveUrl($pageItem))->toBe('/pages/faq');

    // Collection type
    $collection = Collection::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'handle' => 'summer-sale',
        'status' => CollectionStatus::Active,
    ]);
    $collectionItem = NavigationItem::create([
        'menu_id' => $menu->id,
        'title' => 'Summer Sale',
        'type' => NavigationItemType::Collection,
        'resource_id' => $collection->id,
        'position' => 2,
    ]);
    expect($service->resolveUrl($collectionItem))->toBe('/collections/summer-sale');

    // Product type
    $product = Product::factory()->active()->create([
        'store_id' => $this->ctx['store']->id,
        'handle' => 'cool-shirt',
    ]);
    $productItem = NavigationItem::create([
        'menu_id' => $menu->id,
        'title' => 'Cool Shirt',
        'type' => NavigationItemType::Product,
        'resource_id' => $product->id,
        'position' => 3,
    ]);
    expect($service->resolveUrl($productItem))->toBe('/products/cool-shirt');
});
