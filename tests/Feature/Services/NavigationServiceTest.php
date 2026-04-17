<?php

use App\Models\Collection;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Product;
use App\Models\Store;
use App\Services\NavigationService;

it('builds a tree from flat navigation items', function () {
    $menu = NavigationMenu::factory()->create();
    NavigationItem::factory()->create(['menu_id' => $menu->id, 'label' => 'Home', 'url' => '/', 'position' => 0]);
    NavigationItem::factory()->create(['menu_id' => $menu->id, 'label' => 'About', 'url' => '/about', 'position' => 1]);

    $service = app(NavigationService::class);
    $tree = $service->buildTree($menu);

    expect($tree)->toHaveCount(2);
    expect($tree[0]['label'])->toBe('Home');
    expect($tree[0]['url'])->toBe('/');
    expect($tree[1]['label'])->toBe('About');
});

it('resolves url for link type', function () {
    $item = NavigationItem::factory()->create([
        'type' => 'link',
        'url' => 'https://example.com',
    ]);

    $service = app(NavigationService::class);

    expect($service->resolveUrl($item))->toBe('https://example.com');
});

it('resolves url for page type', function () {
    $store = Store::factory()->create();
    $page = Page::factory()->create(['store_id' => $store->id, 'handle' => 'about-us']);

    $item = NavigationItem::factory()->create([
        'type' => 'page',
        'url' => null,
        'resource_id' => $page->id,
    ]);

    $service = app(NavigationService::class);

    expect($service->resolveUrl($item))->toBe('/pages/about-us');
});

it('resolves url for collection type', function () {
    $store = Store::factory()->create();
    $collection = Collection::factory()->create(['store_id' => $store->id, 'handle' => 'summer']);

    $item = NavigationItem::factory()->create([
        'type' => 'collection',
        'url' => null,
        'resource_id' => $collection->id,
    ]);

    $service = app(NavigationService::class);

    expect($service->resolveUrl($item))->toBe('/collections/summer');
});

it('resolves url for product type', function () {
    $store = Store::factory()->create();
    $product = Product::factory()->create(['store_id' => $store->id, 'handle' => 't-shirt']);

    $item = NavigationItem::factory()->create([
        'type' => 'product',
        'url' => null,
        'resource_id' => $product->id,
    ]);

    $service = app(NavigationService::class);

    expect($service->resolveUrl($item))->toBe('/products/t-shirt');
});

it('returns hash when resource is not found', function () {
    $item = NavigationItem::factory()->create([
        'type' => 'page',
        'url' => null,
        'resource_id' => 9999,
    ]);

    $service = app(NavigationService::class);

    expect($service->resolveUrl($item))->toBe('#');
});
