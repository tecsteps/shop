<?php

use App\Enums\NavigationItemType;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;

it('creates a navigation menu with factory', function () {
    $context = createStoreContext();
    $menu = NavigationMenu::factory()->create(['store_id' => $context['store']->id]);

    expect($menu)->toBeInstanceOf(NavigationMenu::class)
        ->and($menu->store_id)->toBe($context['store']->id);
});

it('has items relationship ordered by position', function () {
    $context = createStoreContext();
    $menu = NavigationMenu::factory()->create(['store_id' => $context['store']->id]);

    NavigationItem::factory()->create(['menu_id' => $menu->id, 'label' => 'Second', 'position' => 1]);
    NavigationItem::factory()->create(['menu_id' => $menu->id, 'label' => 'First', 'position' => 0]);

    expect($menu->items)->toHaveCount(2)
        ->and($menu->items->first()->label)->toBe('First')
        ->and($menu->items->last()->label)->toBe('Second');
});

it('navigation item belongs to menu', function () {
    $context = createStoreContext();
    $menu = NavigationMenu::factory()->create(['store_id' => $context['store']->id]);
    $item = NavigationItem::factory()->create(['menu_id' => $menu->id]);

    expect($item->menu->id)->toBe($menu->id);
});

it('casts navigation item type to enum', function () {
    $context = createStoreContext();
    $menu = NavigationMenu::factory()->create(['store_id' => $context['store']->id]);
    $item = NavigationItem::factory()->create(['menu_id' => $menu->id, 'type' => 'collection']);

    expect($item->type)->toBe(NavigationItemType::Collection);
});

it('belongs to store', function () {
    $context = createStoreContext();
    $menu = NavigationMenu::factory()->create(['store_id' => $context['store']->id]);

    expect($menu->store->id)->toBe($context['store']->id);
});

it('enforces unique handle per store', function () {
    $context = createStoreContext();
    NavigationMenu::factory()->create(['store_id' => $context['store']->id, 'handle' => 'main-menu']);

    NavigationMenu::factory()->create(['store_id' => $context['store']->id, 'handle' => 'main-menu']);
})->throws(\Illuminate\Database\UniqueConstraintViolationException::class);
