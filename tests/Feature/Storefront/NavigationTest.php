<?php

use App\Enums\NavigationItemType;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Services\NavigationService;
use Illuminate\Support\Facades\Cache;

it('builds a navigation tree and caches it', function (): void {
    $context = $this->createStoreContext(['hostname' => 'nav-store.test']);

    $menu = NavigationMenu::query()->create([
        'store_id' => $context['store']->id,
        'handle' => 'main-menu',
        'title' => 'Main menu',
    ]);

    NavigationItem::query()->create([
        'menu_id' => $menu->id,
        'type' => NavigationItemType::Link,
        'label' => 'Home',
        'url' => '/',
        'position' => 0,
    ]);

    NavigationItem::query()->create([
        'menu_id' => $menu->id,
        'type' => NavigationItemType::Link,
        'label' => 'Collections',
        'url' => '/collections',
        'position' => 1,
    ]);

    $service = app(NavigationService::class);
    $cacheKey = "navigation:{$context['store']->id}:{$menu->id}";

    expect(Cache::has($cacheKey))->toBeFalse();

    $tree = $service->buildTree($menu);

    expect($tree)->toHaveCount(2);
    expect($tree[0]['label'])->toBe('Home');
    expect($tree[0]['url'])->toBe('/');
    expect($tree[1]['label'])->toBe('Collections');
    expect(Cache::has($cacheKey))->toBeTrue();
});

it('resolves URLs for link items without database hits', function (): void {
    $this->createStoreContext(['hostname' => 'url-store.test']);

    $menu = NavigationMenu::factory()->create();

    $item = NavigationItem::query()->create([
        'menu_id' => $menu->id,
        'type' => NavigationItemType::Link,
        'label' => 'External',
        'url' => 'https://example.com',
        'position' => 0,
    ]);

    $service = app(NavigationService::class);

    expect($service->resolveUrl($item))->toBe('https://example.com');
});
