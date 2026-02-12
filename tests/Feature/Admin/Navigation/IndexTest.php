<?php

use App\Livewire\Admin\Navigation\Index;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Store;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function navigationAdmin(): array
{
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    app()->instance('current_store', $store);

    return [$user, $store];
}

test('it renders the navigation index page', function () {
    [$user, $store] = navigationAdmin();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('Navigation')
        ->assertSuccessful();
});

test('it lists menus for the current store', function () {
    [$user, $store] = navigationAdmin();

    NavigationMenu::factory()->create([
        'store_id' => $store->id,
        'title' => 'Main Menu',
    ]);

    NavigationMenu::factory()->create([
        'store_id' => $store->id,
        'title' => 'Footer Menu',
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('Main Menu')
        ->assertSee('Footer Menu');
});

test('it does not show menus from other stores', function () {
    [$user, $store] = navigationAdmin();

    $otherStore = Store::factory()->create();

    NavigationMenu::factory()->create([
        'store_id' => $store->id,
        'title' => 'My Menu',
    ]);

    NavigationMenu::factory()->create([
        'store_id' => $otherStore->id,
        'title' => 'Other Menu',
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('My Menu')
        ->assertDontSee('Other Menu');
});

test('it creates a new menu', function () {
    [$user, $store] = navigationAdmin();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('openMenuModal')
        ->assertSet('showMenuModal', true)
        ->set('menuTitle', 'Sidebar Menu')
        ->set('menuHandle', 'sidebar-menu')
        ->call('saveMenu')
        ->assertSet('showMenuModal', false)
        ->assertDispatched('toast');

    expect(NavigationMenu::where('store_id', $store->id)->where('title', 'Sidebar Menu')->exists())->toBeTrue();
});

test('it edits an existing menu', function () {
    [$user, $store] = navigationAdmin();

    $menu = NavigationMenu::factory()->create([
        'store_id' => $store->id,
        'title' => 'Old Title',
        'handle' => 'old-title',
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('openMenuModal', $menu->id)
        ->assertSet('menuTitle', 'Old Title')
        ->set('menuTitle', 'New Title')
        ->call('saveMenu')
        ->assertDispatched('toast');

    $menu->refresh();
    expect($menu->title)->toBe('New Title');
});

test('it deletes a menu', function () {
    [$user, $store] = navigationAdmin();

    $menu = NavigationMenu::factory()->create([
        'store_id' => $store->id,
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('deleteMenu', $menu->id)
        ->assertDispatched('toast');

    expect(NavigationMenu::find($menu->id))->toBeNull();
});

test('it creates a navigation item', function () {
    [$user, $store] = navigationAdmin();

    $menu = NavigationMenu::factory()->create([
        'store_id' => $store->id,
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('openItemModal', $menu->id)
        ->assertSet('showItemModal', true)
        ->set('itemLabel', 'Home Link')
        ->set('itemUrl', '/')
        ->set('itemType', 'link')
        ->call('saveItem')
        ->assertSet('showItemModal', false)
        ->assertDispatched('toast');

    expect(NavigationItem::where('menu_id', $menu->id)->where('label', 'Home Link')->exists())->toBeTrue();
});

test('it edits a navigation item', function () {
    [$user, $store] = navigationAdmin();

    $menu = NavigationMenu::factory()->create([
        'store_id' => $store->id,
    ]);

    $item = NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'label' => 'Old Label',
        'url' => '/old',
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('openItemModal', $menu->id, $item->id)
        ->assertSet('itemLabel', 'Old Label')
        ->set('itemLabel', 'New Label')
        ->set('itemUrl', '/new')
        ->call('saveItem')
        ->assertDispatched('toast');

    $item->refresh();
    expect($item->label)->toBe('New Label')
        ->and($item->url)->toBe('/new');
});

test('it deletes a navigation item', function () {
    [$user, $store] = navigationAdmin();

    $menu = NavigationMenu::factory()->create([
        'store_id' => $store->id,
    ]);

    $item = NavigationItem::factory()->create([
        'menu_id' => $menu->id,
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('deleteItem', $item->id)
        ->assertDispatched('toast');

    expect(NavigationItem::find($item->id))->toBeNull();
});

test('it validates required menu title', function () {
    [$user, $store] = navigationAdmin();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('openMenuModal')
        ->set('menuTitle', '')
        ->call('saveMenu')
        ->assertHasErrors(['menuTitle']);
});

test('it validates required item label', function () {
    [$user, $store] = navigationAdmin();

    $menu = NavigationMenu::factory()->create([
        'store_id' => $store->id,
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('openItemModal', $menu->id)
        ->set('itemLabel', '')
        ->call('saveItem')
        ->assertHasErrors(['itemLabel']);
});
