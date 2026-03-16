<?php

use App\Enums\NavigationItemType;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Store;
use App\Services\NavigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
});

it('can create a navigation menu', function () {
    $menu = NavigationMenu::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'main-menu',
        'title' => 'Main Menu',
    ]);

    expect($menu)->toBeInstanceOf(NavigationMenu::class)
        ->and($menu->handle)->toBe('main-menu');
});

it('has items relationship ordered by position', function () {
    $menu = NavigationMenu::factory()->create(['store_id' => $this->store->id]);

    NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'label' => 'Second',
        'position' => 2,
    ]);

    NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'label' => 'First',
        'position' => 1,
    ]);

    $items = $menu->items;

    expect($items)->toHaveCount(2)
        ->and($items->first()->label)->toBe('First')
        ->and($items->last()->label)->toBe('Second');
});

it('scopes navigation menus by store', function () {
    $otherStore = Store::factory()->create();

    NavigationMenu::factory()->create(['store_id' => $this->store->id]);
    NavigationMenu::factory()->create(['store_id' => $otherStore->id]);

    expect(NavigationMenu::query()->count())->toBe(1);
});

it('builds navigation tree with link items', function () {
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

    $service = app(NavigationService::class);
    $tree = $service->buildTree($menu);

    expect($tree)->toHaveCount(2)
        ->and($tree[0]['label'])->toBe('Home')
        ->and($tree[0]['url'])->toBe('/')
        ->and($tree[1]['label'])->toBe('Shop')
        ->and($tree[1]['url'])->toBe('/collections');
});

it('resolves page URLs in navigation', function () {
    $page = Page::factory()->published()->create([
        'store_id' => $this->store->id,
        'handle' => 'about-us',
    ]);

    $menu = NavigationMenu::factory()->create(['store_id' => $this->store->id]);

    NavigationItem::factory()->forPage($page->id)->create([
        'menu_id' => $menu->id,
        'label' => 'About',
        'position' => 0,
    ]);

    $service = app(NavigationService::class);
    $tree = $service->buildTree($menu);

    expect($tree)->toHaveCount(1)
        ->and($tree[0]['url'])->toBe('/pages/about-us');
});

it('returns hash for navigation items with missing resources', function () {
    $menu = NavigationMenu::factory()->create(['store_id' => $this->store->id]);

    NavigationItem::factory()->forPage(99999)->create([
        'menu_id' => $menu->id,
        'label' => 'Missing',
        'position' => 0,
    ]);

    $service = app(NavigationService::class);
    $tree = $service->buildTree($menu);

    expect($tree[0]['url'])->toBe('#');
});

it('enforces unique menu handle per store', function () {
    NavigationMenu::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'main-menu',
    ]);

    expect(fn () => NavigationMenu::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'main-menu',
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

it('casts navigation item type to enum', function () {
    $menu = NavigationMenu::factory()->create(['store_id' => $this->store->id]);

    $item = NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'type' => NavigationItemType::Page,
    ]);

    expect($item->type)->toBe(NavigationItemType::Page);
});
