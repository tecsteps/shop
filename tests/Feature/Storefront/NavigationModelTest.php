<?php

use App\Enums\NavigationItemType;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
});

test('navigation menu belongs to store', function () {
    $menu = NavigationMenu::factory()->create(['store_id' => $this->store->id]);

    expect($menu->store->id)->toBe($this->store->id);
});

test('navigation menu has many items', function () {
    $menu = NavigationMenu::factory()->create(['store_id' => $this->store->id]);
    NavigationItem::factory()->count(3)->create(['menu_id' => $menu->id]);

    expect($menu->items)->toHaveCount(3);
});

test('navigation items are ordered by position', function () {
    $menu = NavigationMenu::factory()->create(['store_id' => $this->store->id]);
    NavigationItem::factory()->create(['menu_id' => $menu->id, 'label' => 'Third', 'position' => 2]);
    NavigationItem::factory()->create(['menu_id' => $menu->id, 'label' => 'First', 'position' => 0]);
    NavigationItem::factory()->create(['menu_id' => $menu->id, 'label' => 'Second', 'position' => 1]);

    $items = $menu->items()->get();

    expect($items[0]->label)->toBe('First')
        ->and($items[1]->label)->toBe('Second')
        ->and($items[2]->label)->toBe('Third');
});

test('navigation item casts type to enum', function () {
    $menu = NavigationMenu::factory()->create(['store_id' => $this->store->id]);
    $item = NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'type' => NavigationItemType::Page,
    ]);

    expect($item->type)->toBe(NavigationItemType::Page);
});

test('navigation item belongs to menu', function () {
    $menu = NavigationMenu::factory()->create(['store_id' => $this->store->id]);
    $item = NavigationItem::factory()->create(['menu_id' => $menu->id]);

    expect($item->menu->id)->toBe($menu->id);
});

test('navigation menu handle is unique per store', function () {
    NavigationMenu::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'main-menu',
    ]);

    expect(fn () => NavigationMenu::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'main-menu',
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});
