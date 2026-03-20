<?php

use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Store;
use Illuminate\Database\QueryException;

it('belongs to a store', function () {
    $menu = NavigationMenu::factory()->create();

    expect($menu->store)->toBeInstanceOf(Store::class);
});

it('has many navigation items', function () {
    $menu = NavigationMenu::factory()->create();
    NavigationItem::factory()->count(5)->create(['menu_id' => $menu->id]);

    expect($menu->items)->toHaveCount(5);
});

it('enforces unique handle per store', function () {
    $store = Store::factory()->create();
    NavigationMenu::factory()->create(['store_id' => $store->id, 'handle' => 'main-menu']);

    NavigationMenu::factory()->create(['store_id' => $store->id, 'handle' => 'main-menu']);
})->throws(QueryException::class);

it('factory creates valid menu', function () {
    $menu = NavigationMenu::factory()->create();

    expect($menu->handle)->not->toBeEmpty();
    expect($menu->title)->not->toBeEmpty();
});

it('cascades delete to menus when store is deleted', function () {
    $store = Store::factory()->create();
    NavigationMenu::factory()->create(['store_id' => $store->id]);

    $store->delete();

    expect(NavigationMenu::where('store_id', $store->id)->count())->toBe(0);
});

it('cascades delete to items when menu is deleted', function () {
    $menu = NavigationMenu::factory()->create();
    NavigationItem::factory()->count(3)->create(['menu_id' => $menu->id]);

    $menu->delete();

    expect(NavigationItem::where('menu_id', $menu->id)->count())->toBe(0);
});
