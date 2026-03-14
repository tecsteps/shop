<?php

use App\Livewire\Admin\Navigation\Index;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ctx = createStoreContext();
});

it('requires authentication for navigation', function () {
    $this->get(route('admin.navigation.index'))
        ->assertRedirect(route('admin.login'));
});

it('renders the navigation page', function () {
    $this->actingAs($this->ctx['user']);

    $this->get(route('admin.navigation.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('lists navigation menus', function () {
    NavigationMenu::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'name' => 'Main Menu',
    ]);

    Livewire::actingAs($this->ctx['user'])
        ->test(Index::class)
        ->assertSee('Main Menu');
});

it('selects a menu and loads its items', function () {
    $menu = NavigationMenu::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'name' => 'Main Menu',
    ]);

    NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'title' => 'Home',
        'type' => 'link',
        'url' => '/',
        'position' => 0,
    ]);

    Livewire::actingAs($this->ctx['user'])
        ->test(Index::class)
        ->call('selectMenu', $menu->id)
        ->assertSet('editingMenuId', $menu->id)
        ->assertCount('menuItems', 1);
});

it('adds a menu item', function () {
    $menu = NavigationMenu::factory()->create([
        'store_id' => $this->ctx['store']->id,
    ]);

    Livewire::actingAs($this->ctx['user'])
        ->test(Index::class)
        ->call('selectMenu', $menu->id)
        ->call('addItem')
        ->set('itemLabel', 'About')
        ->set('itemType', 'link')
        ->set('itemUrl', '/about')
        ->call('saveItem')
        ->assertCount('menuItems', 1);
});

it('removes a menu item', function () {
    $menu = NavigationMenu::factory()->create([
        'store_id' => $this->ctx['store']->id,
    ]);

    NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'title' => 'Home',
        'type' => 'link',
        'url' => '/',
        'position' => 0,
    ]);

    Livewire::actingAs($this->ctx['user'])
        ->test(Index::class)
        ->call('selectMenu', $menu->id)
        ->assertCount('menuItems', 1)
        ->call('removeItem', 0)
        ->assertCount('menuItems', 0);
});

it('saves menu items to the database', function () {
    $menu = NavigationMenu::factory()->create([
        'store_id' => $this->ctx['store']->id,
    ]);

    Livewire::actingAs($this->ctx['user'])
        ->test(Index::class)
        ->call('selectMenu', $menu->id)
        ->call('addItem')
        ->set('itemLabel', 'Products')
        ->set('itemType', 'link')
        ->set('itemUrl', '/products')
        ->call('saveItem')
        ->call('saveMenu')
        ->assertDispatched('toast');

    expect($menu->items()->count())->toBe(1);
    expect($menu->items()->first()->title)->toBe('Products');
});

it('reorders items up and down', function () {
    $menu = NavigationMenu::factory()->create([
        'store_id' => $this->ctx['store']->id,
    ]);

    NavigationItem::factory()->create(['menu_id' => $menu->id, 'title' => 'First', 'type' => 'link', 'position' => 0]);
    NavigationItem::factory()->create(['menu_id' => $menu->id, 'title' => 'Second', 'type' => 'link', 'position' => 1]);

    $component = Livewire::actingAs($this->ctx['user'])
        ->test(Index::class)
        ->call('selectMenu', $menu->id)
        ->assertCount('menuItems', 2);

    $items = $component->get('menuItems');
    expect($items[0]['title'])->toBe('First');
    expect($items[1]['title'])->toBe('Second');

    $component->call('moveItemDown', 0);

    $items = $component->get('menuItems');
    expect($items[0]['title'])->toBe('Second');
    expect($items[1]['title'])->toBe('First');
});
