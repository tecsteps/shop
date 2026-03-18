<?php

use App\Enums\NavigationItemType;
use App\Livewire\Admin\Navigation\Index as NavigationIndex;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use Livewire\Livewire;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->actingAs($this->ctx['user']);
    session(['current_store_id' => $this->ctx['store']->id]);
});

it('requires authentication to access navigation page', function () {
    auth()->logout();
    $this->get('/admin/navigation')->assertRedirect('/admin/login');
});

it('renders the navigation index page', function () {
    $this->get('/admin/navigation')
        ->assertStatus(200)
        ->assertSee('Navigation');
});

it('lists navigation menus', function () {
    NavigationMenu::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Main Menu',
    ]);

    $component = Livewire::test(NavigationIndex::class);
    $component->assertSee('Main Menu');
});

it('creates a new menu', function () {
    $component = Livewire::test(NavigationIndex::class);
    $component->set('newMenuTitle', 'Footer Menu');
    $component->call('createMenu');

    $menu = NavigationMenu::where('store_id', $this->ctx['store']->id)
        ->where('title', 'Footer Menu')
        ->first();

    expect($menu)->not->toBeNull();
    expect($menu->handle)->toBe('footer-menu');
});

it('deletes a menu', function () {
    $menu = NavigationMenu::factory()->create([
        'store_id' => $this->ctx['store']->id,
    ]);

    $component = Livewire::test(NavigationIndex::class);
    $component->call('deleteMenu', $menu->id);

    expect(NavigationMenu::find($menu->id))->toBeNull();
});

it('adds an item to a menu', function () {
    $menu = NavigationMenu::factory()->create([
        'store_id' => $this->ctx['store']->id,
    ]);

    $component = Livewire::test(NavigationIndex::class);
    $component->set('selectedMenuId', $menu->id);
    $component->set('newItemLabel', 'Shop');
    $component->set('newItemUrl', '/collections');
    $component->set('newItemType', 'link');
    $component->call('addItem');

    $item = NavigationItem::where('menu_id', $menu->id)->first();
    expect($item)->not->toBeNull();
    expect($item->label)->toBe('Shop');
    expect($item->url)->toBe('/collections');
    expect($item->type)->toBe(NavigationItemType::Link);
});

it('edits a menu item', function () {
    $menu = NavigationMenu::factory()->create([
        'store_id' => $this->ctx['store']->id,
    ]);

    $item = NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'label' => 'Old Label',
        'url' => '/old',
    ]);

    $component = Livewire::test(NavigationIndex::class);
    $component->set('selectedMenuId', $menu->id);
    $component->call('editItem', $item->id);

    expect($component->get('editingItemId'))->toBe($item->id);
    expect($component->get('editItemLabel'))->toBe('Old Label');

    $component->set('editItemLabel', 'New Label');
    $component->set('editItemUrl', '/new');
    $component->set('editItemType', 'collection');
    $component->call('updateItem');

    $item->refresh();
    expect($item->label)->toBe('New Label');
    expect($item->url)->toBe('/new');
    expect($item->type)->toBe(NavigationItemType::Collection);
});

it('deletes a menu item', function () {
    $menu = NavigationMenu::factory()->create([
        'store_id' => $this->ctx['store']->id,
    ]);

    $item = NavigationItem::factory()->create([
        'menu_id' => $menu->id,
    ]);

    $component = Livewire::test(NavigationIndex::class);
    $component->set('selectedMenuId', $menu->id);
    $component->call('deleteItem', $item->id);

    expect(NavigationItem::find($item->id))->toBeNull();
});

it('reorders items up', function () {
    $menu = NavigationMenu::factory()->create([
        'store_id' => $this->ctx['store']->id,
    ]);

    $first = NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'label' => 'First',
        'position' => 0,
    ]);

    $second = NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'label' => 'Second',
        'position' => 1,
    ]);

    $component = Livewire::test(NavigationIndex::class);
    $component->set('selectedMenuId', $menu->id);
    $component->call('moveItemUp', $second->id);

    $first->refresh();
    $second->refresh();

    expect($second->position)->toBe(0);
    expect($first->position)->toBe(1);
});

it('reorders items down', function () {
    $menu = NavigationMenu::factory()->create([
        'store_id' => $this->ctx['store']->id,
    ]);

    $first = NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'label' => 'First',
        'position' => 0,
    ]);

    $second = NavigationItem::factory()->create([
        'menu_id' => $menu->id,
        'label' => 'Second',
        'position' => 1,
    ]);

    $component = Livewire::test(NavigationIndex::class);
    $component->set('selectedMenuId', $menu->id);
    $component->call('moveItemDown', $first->id);

    $first->refresh();
    $second->refresh();

    expect($first->position)->toBe(1);
    expect($second->position)->toBe(0);
});

it('shows empty state when no menus exist', function () {
    $component = Livewire::test(NavigationIndex::class);
    $component->assertSee('No menus yet');
});

it('supports all navigation item types', function () {
    $menu = NavigationMenu::factory()->create([
        'store_id' => $this->ctx['store']->id,
    ]);

    $component = Livewire::test(NavigationIndex::class);
    $component->set('selectedMenuId', $menu->id);

    foreach (NavigationItemType::cases() as $type) {
        $component->set('newItemLabel', "Item {$type->value}");
        $component->set('newItemUrl', "/{$type->value}");
        $component->set('newItemType', $type->value);
        $component->call('addItem');
    }

    expect(NavigationItem::where('menu_id', $menu->id)->count())->toBe(4);
});
