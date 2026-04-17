<?php

use App\Enums\NavigationItemType;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;

it('belongs to a navigation menu', function () {
    $item = NavigationItem::factory()->create();

    expect($item->menu)->toBeInstanceOf(NavigationMenu::class);
});

it('casts type to NavigationItemType enum', function () {
    $item = NavigationItem::factory()->create(['type' => 'collection']);

    expect($item->type)->toBeInstanceOf(NavigationItemType::class);
    expect($item->type)->toBe(NavigationItemType::Collection);
});

it('link type uses url directly', function () {
    $item = NavigationItem::factory()->create([
        'type' => 'link',
        'url' => 'https://example.com',
        'resource_id' => null,
    ]);

    expect($item->url)->toBe('https://example.com');
    expect($item->resource_id)->toBeNull();
});

it('page type uses resource_id', function () {
    $item = NavigationItem::factory()->create([
        'type' => 'page',
        'url' => null,
        'resource_id' => 5,
    ]);

    expect($item->resource_id)->toBe(5);
    expect($item->url)->toBeNull();
});

it('items are ordered by position', function () {
    $menu = NavigationMenu::factory()->create();
    NavigationItem::factory()->create(['menu_id' => $menu->id, 'position' => 2, 'label' => 'Third']);
    NavigationItem::factory()->create(['menu_id' => $menu->id, 'position' => 0, 'label' => 'First']);
    NavigationItem::factory()->create(['menu_id' => $menu->id, 'position' => 1, 'label' => 'Second']);

    $items = $menu->items;

    expect($items[0]->label)->toBe('First');
    expect($items[1]->label)->toBe('Second');
    expect($items[2]->label)->toBe('Third');
});

it('factory creates valid item', function () {
    $item = NavigationItem::factory()->create();

    expect($item->type)->toBeInstanceOf(NavigationItemType::class);
    expect($item->label)->not->toBeEmpty();
    expect($item->position)->toBeGreaterThanOrEqual(0);
});
