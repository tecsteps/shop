<?php

use App\Livewire\Admin\Navigation\Index as NavigationIndex;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

it('creates a navigation menu', function (): void {
    [$user, $store] = loginAsAdmin();

    Livewire::test(NavigationIndex::class)
        ->set('newMenuTitle', 'Main menu')
        ->call('createMenu');

    $menu = NavigationMenu::where('title', 'Main menu')->first();
    expect($menu)->not->toBeNull()
        ->and($menu->store_id)->toBe($store->id)
        ->and($menu->handle)->toBe('main-menu');
});

it('adds an item to a menu', function (): void {
    [$user, $store] = loginAsAdmin();

    $menu = NavigationMenu::create([
        'store_id' => $store->id,
        'title' => 'Main',
        'handle' => 'main',
    ]);

    Livewire::test(NavigationIndex::class)
        ->call('openItemModal', $menu->id)
        ->set('newItemType', 'link')
        ->set('newItemLabel', 'Home')
        ->set('newItemUrl', '/')
        ->call('addItem');

    $item = NavigationItem::where('menu_id', $menu->id)->first();
    expect($item)->not->toBeNull()
        ->and($item->label)->toBe('Home')
        ->and($item->url)->toBe('/');
});
