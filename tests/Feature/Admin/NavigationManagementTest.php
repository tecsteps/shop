<?php

use App\Enums\StoreUserRole;
use App\Livewire\Admin\Navigation\Index as NavigationIndex;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Services\NavigationService;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->user = $this->context['user'];

    $this->menu = NavigationMenu::factory()->for($this->store)->create([
        'handle' => 'main-menu',
        'title' => 'Main Menu',
    ]);
});

it('lists navigation menus', function () {
    NavigationMenu::factory()->for($this->store)->create(['handle' => 'footer-menu', 'title' => 'Footer Menu']);

    actingAsAdmin($this->user)
        ->get('/admin/navigation')
        ->assertOk()
        ->assertSee('Main Menu')
        ->assertSee('Footer Menu');
});

it('adds a link item to a menu', function () {
    actingAsAdmin($this->user);

    Livewire::test(NavigationIndex::class)
        ->call('selectMenu', $this->menu->getKey())
        ->call('addItem')
        ->set('itemLabel', 'Contact')
        ->set('itemType', 'link')
        ->set('itemUrl', '/contact')
        ->call('saveItem')
        ->assertHasNoErrors()
        ->call('saveMenu')
        ->assertDispatched('toast');

    $this->assertDatabaseHas('navigation_items', [
        'menu_id' => $this->menu->getKey(),
        'label' => 'Contact',
        'type' => 'link',
        'url' => '/contact',
        'position' => 0,
    ]);
});

it('adds a page item with a resource picker', function () {
    $page = Page::factory()->for($this->store)->create(['title' => 'About Us']);

    actingAsAdmin($this->user);

    Livewire::test(NavigationIndex::class)
        ->call('selectMenu', $this->menu->getKey())
        ->call('addItem')
        ->set('itemLabel', 'About')
        ->set('itemType', 'page')
        ->set('itemResourceId', (string) $page->getKey())
        ->call('saveItem')
        ->assertHasNoErrors()
        ->call('saveMenu');

    $this->assertDatabaseHas('navigation_items', [
        'menu_id' => $this->menu->getKey(),
        'label' => 'About',
        'type' => 'page',
        'resource_id' => $page->getKey(),
    ]);
});

it('requires a url for link items and a resource for resource items', function () {
    actingAsAdmin($this->user);

    $component = Livewire::test(NavigationIndex::class)
        ->call('selectMenu', $this->menu->getKey())
        ->call('addItem')
        ->set('itemLabel', 'Broken')
        ->set('itemType', 'link')
        ->set('itemUrl', '')
        ->call('saveItem')
        ->assertHasErrors(['itemUrl']);

    $component
        ->set('itemType', 'collection')
        ->set('itemResourceId', '')
        ->call('saveItem')
        ->assertHasErrors(['itemResourceId']);
});

it('edits and removes menu items', function () {
    NavigationItem::factory()->for($this->menu, 'menu')->create(['label' => 'Home', 'position' => 0]);
    NavigationItem::factory()->for($this->menu, 'menu')->create(['label' => 'Old Label', 'position' => 1]);

    actingAsAdmin($this->user);

    Livewire::test(NavigationIndex::class)
        ->call('selectMenu', $this->menu->getKey())
        ->call('editItem', 1)
        ->set('itemLabel', 'New Label')
        ->call('saveItem')
        ->call('removeItem', 0)
        ->call('saveMenu')
        ->assertDispatched('toast');

    expect($this->menu->items()->pluck('label')->all())->toBe(['New Label']);
});

it('reorders menu items and persists positions', function () {
    NavigationItem::factory()->for($this->menu, 'menu')->create(['label' => 'First', 'position' => 0]);
    NavigationItem::factory()->for($this->menu, 'menu')->create(['label' => 'Second', 'position' => 1]);
    NavigationItem::factory()->for($this->menu, 'menu')->create(['label' => 'Third', 'position' => 2]);

    actingAsAdmin($this->user);

    Livewire::test(NavigationIndex::class)
        ->call('selectMenu', $this->menu->getKey())
        ->call('reorderItems', 2, 0)
        ->call('saveMenu');

    expect($this->menu->items()->orderBy('position')->pluck('label')->all())
        ->toBe(['Third', 'First', 'Second']);
});

it('invalidates the cached navigation tree on save', function () {
    NavigationItem::factory()->for($this->menu, 'menu')->create(['label' => 'Home', 'url' => '/', 'position' => 0]);

    $service = app(NavigationService::class);

    expect(collect($service->tree('main-menu'))->pluck('label')->all())->toBe(['Home']);

    actingAsAdmin($this->user);

    Livewire::test(NavigationIndex::class)
        ->call('selectMenu', $this->menu->getKey())
        ->call('addItem')
        ->set('itemLabel', 'Contact')
        ->set('itemType', 'link')
        ->set('itemUrl', '/contact')
        ->call('saveItem')
        ->call('saveMenu');

    expect(collect($service->tree('main-menu'))->pluck('label')->all())->toBe(['Home', 'Contact']);
});

it('restricts navigation management by role', function () {
    $support = createStoreMember($this->store, StoreUserRole::Support);

    actingAsAdmin($support, $this->store)
        ->get('/admin/navigation')
        ->assertForbidden();

    $staff = createStoreMember($this->store, StoreUserRole::Staff);

    actingAsAdmin($staff, $this->store)
        ->get('/admin/navigation')
        ->assertOk();

    Livewire::test(NavigationIndex::class)
        ->call('selectMenu', $this->menu->getKey())
        ->call('saveMenu')
        ->assertForbidden();
});
