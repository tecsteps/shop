<?php

use App\Models\Collection as ProductCollection;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Services\NavigationService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->service = app(NavigationService::class);
});

it('builds a nested tree with one level of children', function () {
    $menu = NavigationMenu::factory()->mainMenu()->create(['store_id' => $this->store->id]);

    $shop = NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'label' => 'Shop',
        'url' => '/collections',
        'position' => 0,
    ]);
    NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'label' => 'About',
        'url' => '/pages/about',
        'position' => 1,
    ]);
    NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'parent_id' => $shop->id,
        'label' => 'New arrivals',
        'url' => '/collections/new',
        'position' => 0,
    ]);

    $tree = $this->service->buildTree($menu);

    expect($tree)->toHaveCount(2);
    expect($tree[0]['label'])->toBe('Shop');
    expect($tree[0]['children'])->toHaveCount(1);
    expect($tree[0]['children'][0]['label'])->toBe('New arrivals');
    expect($tree[1]['children'])->toBe([]);
});

it('orders items by position', function () {
    $menu = NavigationMenu::factory()->create(['store_id' => $this->store->id]);

    NavigationItem::factory()->create(['menu_id' => $menu->id, 'label' => 'Second', 'position' => 2]);
    NavigationItem::factory()->create(['menu_id' => $menu->id, 'label' => 'First', 'position' => 1]);

    $tree = $this->service->buildTree($menu);

    expect(array_column($tree, 'label'))->toBe(['First', 'Second']);
});

it('resolves a link item to its url', function () {
    $menu = NavigationMenu::factory()->create(['store_id' => $this->store->id]);
    $item = NavigationItem::factory()->create(['menu_id' => $menu->id, 'url' => '/custom']);

    expect($this->service->resolveUrl($item))->toBe('/custom');
});

it('resolves a page item to /pages/{handle}', function () {
    $menu = NavigationMenu::factory()->create(['store_id' => $this->store->id]);
    $page = Page::factory()->create(['store_id' => $this->store->id, 'handle' => 'about']);
    $item = NavigationItem::factory()->page($page->id)->create(['menu_id' => $menu->id]);

    expect($this->service->resolveUrl($item))->toBe('/pages/about');
});

it('resolves a collection item to /collections/{handle}', function () {
    $menu = NavigationMenu::factory()->create(['store_id' => $this->store->id]);
    $collection = ProductCollection::factory()->create(['store_id' => $this->store->id, 'handle' => 'summer']);
    $item = NavigationItem::factory()->collection($collection->id)->create(['menu_id' => $menu->id]);

    expect($this->service->resolveUrl($item))->toBe('/collections/summer');
});

it('falls back to # when a resource cannot be resolved', function () {
    $menu = NavigationMenu::factory()->create(['store_id' => $this->store->id]);
    $item = NavigationItem::factory()->page(999999)->create(['menu_id' => $menu->id]);

    expect($this->service->resolveUrl($item))->toBe('#');
});

it('caches the resolved tree per store and handle', function () {
    $menu = NavigationMenu::factory()->mainMenu()->create(['store_id' => $this->store->id]);
    NavigationItem::factory()->create(['menu_id' => $menu->id, 'label' => 'Home', 'url' => '/']);

    expect(Cache::has("navigation:{$this->store->id}:main-menu"))->toBeFalse();

    $this->service->tree('main-menu');

    expect(Cache::has("navigation:{$this->store->id}:main-menu"))->toBeTrue();
});

it('returns null for an unknown menu handle', function () {
    expect($this->service->tree('does-not-exist'))->toBeNull();
});
