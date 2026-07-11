<?php

use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Store;
use App\Services\NavigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

test('navigation service orders items and resolves direct and resource urls', function () {
    Cache::clear();
    $store = Store::factory()->create();
    $menu = NavigationMenu::factory()->for($store)->create();
    $page = Page::factory()->for($store)->create(['handle' => 'about-us']);
    NavigationItem::factory()->for($menu, 'menu')->page($page->id)->create([
        'label' => 'About',
        'position' => 2,
    ]);
    NavigationItem::factory()->for($menu, 'menu')->create([
        'label' => 'Home',
        'url' => '/',
        'position' => 1,
    ]);

    $tree = app(NavigationService::class)->buildTree($menu);

    expect($tree)->toHaveCount(2)
        ->and($tree[0]['label'])->toBe('Home')
        ->and($tree[0]['url'])->toBe('/')
        ->and($tree[1]['label'])->toBe('About')
        ->and($tree[1]['url'])->toBe('/pages/about-us')
        ->and($tree[1]['children'])->toBe([]);
});
