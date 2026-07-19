<?php

use App\Livewire\Admin\Navigation\Index;
use App\Models\Collection;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Product;
use App\Services\NavigationService;
use Livewire\Livewire;

beforeEach(function () {
    $this->store = $this->createStore();
    $this->user = $this->createUserWithRole($this->store, 'owner');
    $this->bindStore($this->store);
});

test('renders the navigation page', function () {
    NavigationMenu::factory()->mainMenu()->create(['store_id' => $this->store->id]);

    $this->actingAs($this->user)
        ->withSession(['current_store_id' => $this->store->id])
        ->get('/admin/navigation')
        ->assertOk()
        ->assertSee('Main menu');
});

test('creates a menu', function () {
    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->set('menuTitle', 'Main menu')
        ->set('menuHandle', 'main-menu')
        ->call('createMenu')
        ->assertHasNoErrors()
        ->assertDispatched('toast')
        ->assertSet('selectedMenuId', NavigationMenu::query()->where('handle', 'main-menu')->sole()->id);

    $this->assertDatabaseHas('navigation_menus', [
        'store_id' => $this->store->id,
        'handle' => 'main-menu',
        'title' => 'Main menu',
    ]);
});

test('adds items of every type and resolves their urls', function () {
    $menu = NavigationMenu::factory()->mainMenu()->create(['store_id' => $this->store->id]);
    $page = Page::factory()->create(['store_id' => $this->store->id, 'handle' => 'about']);
    $collection = Collection::factory()->create(['store_id' => $this->store->id, 'handle' => 'summer']);
    $product = Product::factory()->create(['store_id' => $this->store->id, 'handle' => 'shirt']);
    $service = app(NavigationService::class);

    Livewire::actingAs($this->user);

    Livewire::test(Index::class, ['selectedMenuId' => $menu->id])
        ->set('itemLabel', 'Home')
        ->set('itemType', 'link')
        ->set('itemUrl', '/')
        ->call('saveItem')
        ->assertHasNoErrors()
        ->set('itemLabel', 'About')
        ->set('itemType', 'page')
        ->set('itemResourceId', $page->id)
        ->call('saveItem')
        ->assertHasNoErrors()
        ->set('itemLabel', 'Summer')
        ->set('itemType', 'collection')
        ->set('itemResourceId', $collection->id)
        ->call('saveItem')
        ->assertHasNoErrors()
        ->set('itemLabel', 'Shirt')
        ->set('itemType', 'product')
        ->set('itemResourceId', $product->id)
        ->call('saveItem')
        ->assertHasNoErrors();

    $items = $menu->items()->orderBy('position')->get();

    expect($items)->toHaveCount(4)
        ->and($service->resolveUrl($items[0]))->toBe('/')
        ->and($service->resolveUrl($items[1]))->toBe('/pages/about')
        ->and($service->resolveUrl($items[2]))->toBe('/collections/summer')
        ->and($service->resolveUrl($items[3]))->toBe('/products/shirt');
});

test('resource items require an existing store resource', function () {
    $menu = NavigationMenu::factory()->create(['store_id' => $this->store->id]);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class, ['selectedMenuId' => $menu->id])
        ->set('itemLabel', 'Ghost page')
        ->set('itemType', 'page')
        ->set('itemResourceId', 99999)
        ->call('saveItem')
        ->assertHasErrors(['itemResourceId']);
});

test('reorders items and persists positions', function () {
    $menu = NavigationMenu::factory()->create(['store_id' => $this->store->id]);
    $first = NavigationItem::factory()->create(['menu_id' => $menu->id, 'label' => 'First', 'position' => 0]);
    $second = NavigationItem::factory()->create(['menu_id' => $menu->id, 'label' => 'Second', 'position' => 1]);
    $third = NavigationItem::factory()->create(['menu_id' => $menu->id, 'label' => 'Third', 'position' => 2]);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class, ['selectedMenuId' => $menu->id])
        ->call('moveItem', $third->id, 'up');

    expect($menu->items()->orderBy('position')->pluck('label')->all())->toBe(['First', 'Third', 'Second'])
        ->and($second->refresh()->position)->toBe(2)
        ->and($third->refresh()->position)->toBe(1);
});

test('removes an item', function () {
    $menu = NavigationMenu::factory()->create(['store_id' => $this->store->id]);
    $item = NavigationItem::factory()->create(['menu_id' => $menu->id, 'position' => 0]);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class, ['selectedMenuId' => $menu->id])
        ->call('removeItem', $item->id)
        ->assertDispatched('toast');

    $this->assertDatabaseMissing('navigation_items', ['id' => $item->id]);
});

test('staff can view but not manage navigation', function () {
    $staff = $this->createUserWithRole($this->store, 'staff');
    $menu = NavigationMenu::factory()->create(['store_id' => $this->store->id]);

    $this->actingAs($staff)
        ->withSession(['current_store_id' => $this->store->id])
        ->get('/admin/navigation')
        ->assertOk();

    Livewire::actingAs($staff);
    Livewire::test(Index::class, ['selectedMenuId' => $menu->id])
        ->set('menuTitle', 'Blocked menu')
        ->set('menuHandle', 'blocked-menu')
        ->call('createMenu')
        ->assertForbidden();

    Livewire::test(Index::class, ['selectedMenuId' => $menu->id])
        ->set('itemLabel', 'Blocked')
        ->set('itemType', 'link')
        ->set('itemUrl', '/blocked')
        ->call('saveItem')
        ->assertForbidden();
});
