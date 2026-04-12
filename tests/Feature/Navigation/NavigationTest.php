<?php

use App\Enums\NavigationItemType;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Store;
use App\Services\NavigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
    $this->service = app(NavigationService::class);
});

afterEach(function (): void {
    app()->forgetInstance('current_store');
    Cache::flush();
});

it('builds an ordered tree of navigation items', function (): void {
    $menu = NavigationMenu::factory()->create();

    NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'label' => 'Third',
        'position' => 30,
    ]);
    NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'label' => 'First',
        'position' => 10,
    ]);
    NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'label' => 'Second',
        'position' => 20,
    ]);

    $tree = $this->service->buildTree($menu);

    expect($tree)->toHaveCount(3)
        ->and($tree[0]['label'])->toBe('First')
        ->and($tree[1]['label'])->toBe('Second')
        ->and($tree[2]['label'])->toBe('Third');
});

it('resolves placeholder URLs for page, collection and product items', function (): void {
    $menu = NavigationMenu::factory()->create();

    $pageItem = NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'type' => NavigationItemType::Page->value,
        'url' => null,
        'resource_id' => 42,
    ]);
    $collectionItem = NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'type' => NavigationItemType::Collection->value,
        'url' => null,
        'resource_id' => 7,
    ]);
    $productItem = NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'type' => NavigationItemType::Product->value,
        'url' => null,
        'resource_id' => 99,
    ]);
    $linkItem = NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'type' => NavigationItemType::Link->value,
        'url' => '/external',
    ]);

    expect($pageItem->resolveUrl())->toBe('/pages/42')
        ->and($collectionItem->resolveUrl())->toBe('/collections/7')
        ->and($productItem->resolveUrl())->toBe('/products/99')
        ->and($linkItem->resolveUrl())->toBe('/external');
});

it('caches the menu tree', function (): void {
    $menu = NavigationMenu::factory()->create();
    NavigationItem::factory()->count(2)->create(['menu_id' => $menu->id]);

    $this->service->buildTree($menu);

    expect(Cache::has("nav:menu:{$menu->id}"))->toBeTrue();
});
