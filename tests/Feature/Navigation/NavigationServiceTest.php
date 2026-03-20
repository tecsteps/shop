<?php

use App\Enums\NavigationItemType;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Services\NavigationService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->navigationService = new NavigationService;
});

it('builds a navigation tree from menu items', function () {
    $menu = NavigationMenu::factory()->create([
        'store_id' => $this->context['store']->id,
        'handle' => 'main-menu',
    ]);

    NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'label' => 'Home',
        'url' => '/',
        'position' => 0,
    ]);

    NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'label' => 'About',
        'url' => '/about',
        'position' => 1,
    ]);

    $tree = $this->navigationService->buildTree($menu);

    expect($tree)->toHaveCount(2)
        ->and($tree[0]['label'])->toBe('Home')
        ->and($tree[0]['url'])->toBe('/')
        ->and($tree[1]['label'])->toBe('About')
        ->and($tree[1]['url'])->toBe('/about');
});

it('resolves link type URLs directly', function () {
    $item = new NavigationItem([
        'type' => NavigationItemType::Link,
        'url' => '/custom-page',
    ]);

    $url = $this->navigationService->resolveUrl($item);

    expect($url)->toBe('/custom-page');
});

it('resolves page type URLs via handle', function () {
    $page = Page::factory()->create([
        'store_id' => $this->context['store']->id,
        'handle' => 'about-us',
    ]);

    $item = new NavigationItem([
        'type' => NavigationItemType::Page,
        'resource_id' => $page->id,
    ]);

    $url = $this->navigationService->resolveUrl($item);

    expect($url)->toBe('/pages/about-us');
});

it('returns fallback URL for missing page resources', function () {
    $item = new NavigationItem([
        'type' => NavigationItemType::Page,
        'resource_id' => 99999,
    ]);

    $url = $this->navigationService->resolveUrl($item);

    expect($url)->toBe('/');
});

it('returns items ordered by position', function () {
    $menu = NavigationMenu::factory()->create([
        'store_id' => $this->context['store']->id,
    ]);

    NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'label' => 'Third',
        'position' => 2,
    ]);

    NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'label' => 'First',
        'position' => 0,
    ]);

    NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'label' => 'Second',
        'position' => 1,
    ]);

    $tree = $this->navigationService->buildTree($menu);

    expect($tree[0]['label'])->toBe('First')
        ->and($tree[1]['label'])->toBe('Second')
        ->and($tree[2]['label'])->toBe('Third');
});

it('creates navigation menu with factory', function () {
    $menu = NavigationMenu::factory()->create([
        'store_id' => $this->context['store']->id,
        'handle' => 'test-menu',
        'title' => 'Test Menu',
    ]);

    expect($menu->handle)->toBe('test-menu')
        ->and($menu->title)->toBe('Test Menu')
        ->and($menu->store->id)->toBe($this->context['store']->id);
});

it('scopes navigation menus to current store', function () {
    NavigationMenu::factory()->create([
        'store_id' => $this->context['store']->id,
    ]);

    $otherStore = \App\Models\Store::factory()->create();
    NavigationMenu::factory()->create([
        'store_id' => $otherStore->id,
    ]);

    expect(NavigationMenu::count())->toBe(1);
});

it('has items relationship ordered by position', function () {
    $menu = NavigationMenu::factory()->create([
        'store_id' => $this->context['store']->id,
    ]);

    NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'label' => 'Second',
        'position' => 1,
    ]);

    NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'label' => 'First',
        'position' => 0,
    ]);

    $items = $menu->items;

    expect($items)->toHaveCount(2)
        ->and($items[0]->label)->toBe('First')
        ->and($items[1]->label)->toBe('Second');
});
