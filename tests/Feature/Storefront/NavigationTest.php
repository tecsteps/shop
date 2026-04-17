<?php

use App\Enums\NavigationItemType;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Services\NavigationService;

it('creates a navigation menu with store relationship', function () {
    $ctx = createStoreContext();

    $menu = NavigationMenu::factory()->create([
        'store_id' => $ctx['store']->id,
        'handle' => 'main-menu',
        'title' => 'Main Menu',
    ]);

    expect($menu->store->id)->toBe($ctx['store']->id)
        ->and($menu->handle)->toBe('main-menu');
});

it('has navigation items relationship ordered by position', function () {
    $ctx = createStoreContext();
    $menu = NavigationMenu::factory()->create(['store_id' => $ctx['store']->id]);

    NavigationItem::factory()->create(['menu_id' => $menu->id, 'label' => 'Third', 'position' => 2]);
    NavigationItem::factory()->create(['menu_id' => $menu->id, 'label' => 'First', 'position' => 0]);
    NavigationItem::factory()->create(['menu_id' => $menu->id, 'label' => 'Second', 'position' => 1]);

    $items = $menu->items;

    expect($items)->toHaveCount(3)
        ->and($items->first()->label)->toBe('First')
        ->and($items->last()->label)->toBe('Third');
});

it('scopes navigation menus to current store', function () {
    $ctx = createStoreContext();
    NavigationMenu::factory()->count(2)->create(['store_id' => $ctx['store']->id]);

    $otherStore = \App\Models\Store::factory()->create(['organization_id' => $ctx['organization']->id]);
    NavigationMenu::factory()->count(3)->create(['store_id' => $otherStore->id]);

    expect(NavigationMenu::count())->toBe(2);
});

it('casts navigation item type to enum', function () {
    $ctx = createStoreContext();
    $menu = NavigationMenu::factory()->create(['store_id' => $ctx['store']->id]);

    $item = NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'type' => NavigationItemType::Page,
    ]);

    expect($item->type)->toBe(NavigationItemType::Page);
});

it('builds navigation tree via NavigationService', function () {
    $ctx = createStoreContext();
    $menu = NavigationMenu::factory()->create(['store_id' => $ctx['store']->id]);

    NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'label' => 'Home',
        'url' => '/',
        'type' => NavigationItemType::Link,
        'position' => 0,
    ]);

    NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'label' => 'Collections',
        'url' => '/collections',
        'type' => NavigationItemType::Link,
        'position' => 1,
    ]);

    $service = app(NavigationService::class);
    $tree = $service->buildTree($menu);

    expect($tree)->toHaveCount(2)
        ->and($tree->first()['label'])->toBe('Home')
        ->and($tree->first()['url'])->toBe('/');
});

it('resolves URL for different navigation item types', function () {
    $service = app(NavigationService::class);

    $linkItem = new NavigationItem([
        'type' => NavigationItemType::Link,
        'url' => '/custom-url',
    ]);

    $pageItem = new NavigationItem([
        'type' => NavigationItemType::Page,
        'resource_id' => 5,
    ]);

    $collectionItem = new NavigationItem([
        'type' => NavigationItemType::Collection,
        'resource_id' => 10,
    ]);

    $productItem = new NavigationItem([
        'type' => NavigationItemType::Product,
        'resource_id' => 15,
    ]);

    expect($service->resolveUrl($linkItem))->toBe('/custom-url')
        ->and($service->resolveUrl($pageItem))->toBe('/pages/5')
        ->and($service->resolveUrl($collectionItem))->toBe('/collections/10')
        ->and($service->resolveUrl($productItem))->toBe('/products/15');
});
