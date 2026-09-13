<?php

use App\Enums\NavigationItemType;
use App\Livewire\Admin\Navigation\Index;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    ['store' => $this->store, 'user' => $this->user] = $this->createStoreContext();
    $this->actingAsAdmin($this->user, $this->store);
});

it('lists navigation menus scoped to the current store', function () {
    NavigationMenu::factory()->create(['store_id' => $this->store->getKey(), 'handle' => 'main-menu', 'title' => 'Main Menu']);

    $this->get(route('admin.navigation.index'))
        ->assertOk()
        ->assertSee('Main Menu');
});

it('adds and saves menu items', function () {
    $menu = NavigationMenu::factory()->create(['store_id' => $this->store->getKey()]);

    Livewire::test(Index::class)
        ->call('selectMenu', $menu->getKey())
        ->call('addItem')
        ->set('itemLabel', 'Home')
        ->set('itemType', 'link')
        ->set('itemUrl', '/')
        ->call('saveItem')
        ->call('saveMenu')
        ->assertDispatched('toast');

    $item = NavigationItem::query()->where('menu_id', $menu->getKey())->firstOrFail();

    expect($item->label)->toBe('Home')
        ->and($item->type)->toBe(NavigationItemType::Link)
        ->and($item->position)->toBe(0);
});

it('reorders and removes menu items', function () {
    $menu = NavigationMenu::factory()->create(['store_id' => $this->store->getKey()]);
    NavigationItem::factory()->create(['menu_id' => $menu->getKey(), 'label' => 'First', 'position' => 0]);
    NavigationItem::factory()->create(['menu_id' => $menu->getKey(), 'label' => 'Second', 'position' => 1]);

    Livewire::test(Index::class)
        ->call('selectMenu', $menu->getKey())
        ->call('moveItemDown', 0)
        ->call('removeItem', 1)
        ->call('saveMenu')
        ->assertDispatched('toast');

    expect(NavigationItem::query()->where('menu_id', $menu->getKey())->pluck('label')->all())->toBe(['Second']);
});

it('does not show menus from another store', function () {
    ['store' => $otherStore] = $this->createStoreContext();
    NavigationMenu::factory()->create(['store_id' => $otherStore->getKey(), 'title' => 'Other Store Menu']);

    $this->get(route('admin.navigation.index'))
        ->assertOk()
        ->assertDontSee('Other Store Menu');
});
