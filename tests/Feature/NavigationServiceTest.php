<?php

use App\Enums\NavigationItemType;
use App\Models\Collection;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Product;
use App\Services\NavigationService;

it('builds a navigation tree from menu items', function () {
    $context = createStoreContext();
    $menu = NavigationMenu::factory()->create(['store_id' => $context['store']->id]);

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
        'label' => 'Blog',
        'url' => '/blog',
        'position' => 1,
    ]);

    $service = new NavigationService;
    $tree = $service->buildTree($menu);

    expect($tree)->toHaveCount(2)
        ->and($tree[0]['label'])->toBe('Home')
        ->and($tree[0]['url'])->toBe('/')
        ->and($tree[1]['label'])->toBe('Blog')
        ->and($tree[1]['url'])->toBe('/blog');
});

it('resolves collection URL', function () {
    $context = createStoreContext();
    $collection = Collection::factory()->create([
        'store_id' => $context['store']->id,
        'handle' => 'summer-sale',
    ]);

    $menu = NavigationMenu::factory()->create(['store_id' => $context['store']->id]);
    $item = NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'type' => NavigationItemType::Collection,
        'label' => 'Summer Sale',
        'resource_id' => $collection->id,
        'position' => 0,
    ]);

    $service = new NavigationService;
    $url = $service->resolveUrl($item);

    expect($url)->toBe('/collections/summer-sale');
});

it('resolves page URL', function () {
    $context = createStoreContext();
    $page = Page::factory()->create([
        'store_id' => $context['store']->id,
        'handle' => 'about-us',
    ]);

    $menu = NavigationMenu::factory()->create(['store_id' => $context['store']->id]);
    $item = NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'type' => NavigationItemType::Page,
        'label' => 'About Us',
        'resource_id' => $page->id,
        'position' => 0,
    ]);

    $service = new NavigationService;
    $url = $service->resolveUrl($item);

    expect($url)->toBe('/pages/about-us');
});

it('resolves product URL', function () {
    $context = createStoreContext();
    $product = Product::factory()->create([
        'store_id' => $context['store']->id,
        'handle' => 'cool-shirt',
    ]);

    $menu = NavigationMenu::factory()->create(['store_id' => $context['store']->id]);
    $item = NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'type' => NavigationItemType::Product,
        'label' => 'Cool Shirt',
        'resource_id' => $product->id,
        'position' => 0,
    ]);

    $service = new NavigationService;
    $url = $service->resolveUrl($item);

    expect($url)->toBe('/products/cool-shirt');
});

it('returns hash for missing resource', function () {
    $context = createStoreContext();
    $menu = NavigationMenu::factory()->create(['store_id' => $context['store']->id]);
    $item = NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'type' => NavigationItemType::Collection,
        'label' => 'Missing',
        'resource_id' => 9999,
        'position' => 0,
    ]);

    $service = new NavigationService;
    $url = $service->resolveUrl($item);

    expect($url)->toBe('#');
});
