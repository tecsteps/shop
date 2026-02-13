<?php

use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Services\NavigationService;

it('builds navigation tree from menu items', function () {
    $context = createStoreContext();

    $menu = NavigationMenu::factory()->create([
        'store_id' => $context['store']->id,
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

    $service = new NavigationService;
    $tree = $service->buildTree($menu);

    expect($tree)->toHaveCount(2)
        ->and($tree[0]['label'])->toBe('Home')
        ->and($tree[0]['url'])->toBe('/')
        ->and($tree[1]['label'])->toBe('About')
        ->and($tree[1]['url'])->toBe('/about');
});

it('resolves URLs for page type items', function () {
    $context = createStoreContext();

    $page = Page::factory()->published()->create([
        'store_id' => $context['store']->id,
        'handle' => 'about-us',
    ]);

    $menu = NavigationMenu::factory()->create([
        'store_id' => $context['store']->id,
    ]);

    NavigationItem::factory()->page($page->id)->create([
        'menu_id' => $menu->id,
        'label' => 'About Us',
        'position' => 0,
    ]);

    $service = new NavigationService;
    $tree = $service->buildTree($menu);

    expect($tree)->toHaveCount(1)
        ->and($tree[0]['url'])->toBe('/pages/about-us')
        ->and($tree[0]['type'])->toBe('page');
});

it('resolves URLs for collection type items', function () {
    $context = createStoreContext();

    $menu = NavigationMenu::factory()->create([
        'store_id' => $context['store']->id,
    ]);

    NavigationItem::factory()->collection(42)->create([
        'menu_id' => $menu->id,
        'label' => 'Summer Sale',
        'position' => 0,
    ]);

    $service = new NavigationService;
    $tree = $service->buildTree($menu);

    expect($tree)->toHaveCount(1)
        ->and($tree[0]['url'])->toBe('/collections/42')
        ->and($tree[0]['type'])->toBe('collection');
});

it('resolves URLs for link type items', function () {
    $context = createStoreContext();

    $menu = NavigationMenu::factory()->create([
        'store_id' => $context['store']->id,
    ]);

    NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'label' => 'External',
        'url' => 'https://example.com',
        'position' => 0,
    ]);

    $service = new NavigationService;
    $tree = $service->buildTree($menu);

    expect($tree)->toHaveCount(1)
        ->and($tree[0]['url'])->toBe('https://example.com')
        ->and($tree[0]['type'])->toBe('link');
});
