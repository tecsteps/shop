<?php

use App\Livewire\Admin\Navigation\Index as NavigationIndex;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

function navigationAdmin(Store $store, string $role = 'owner'): User
{
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => $role]);

    return $user;
}

it('renders the navigation editor with current store menus', function () {
    $store = shopStore('navigation-admin.test');
    $user = navigationAdmin($store);

    $this->actingAs($user)
        ->withSession(['current_store_id' => $store->id])
        ->get(route('admin.navigation'))
        ->assertOk()
        ->assertSee('Navigation')
        ->assertSee('Main Menu')
        ->assertSee('Footer Menu')
        ->assertSee('Add item');

    expect(NavigationMenu::query()->where('store_id', $store->id)->count())->toBe(2);
});

it('creates edits nests and saves store-scoped navigation items for the storefront', function () {
    $store = shopStore('navigation-menu.test');
    $otherStore = shopStore('navigation-menu-other.test');
    $user = User::factory()->create();
    $user->stores()->attach([$store->id => ['role' => 'owner'], $otherStore->id => ['role' => 'owner']]);
    app()->instance('current_store', $store);

    $component = Livewire::actingAs($user)->test(NavigationIndex::class);
    $mainMenu = NavigationMenu::query()->where('store_id', $store->id)->where('handle', 'main-menu')->firstOrFail();
    app()->forgetInstance('current_store');

    $component->call('selectMenu', $mainMenu->id)
        ->call('addItem')
        ->set('itemLabel', 'Departments')
        ->set('itemUrl', '/collections')
        ->set('itemParentId', '')
        ->call('saveItem')
        ->assertHasNoErrors();

    $rootId = $component->get('menuItems')[0]['id'];

    $component->call('addItem', $rootId)
        ->set('itemLabel', 'Summer collection')
        ->set('itemUrl', '/collections/summer')
        ->call('saveItem')
        ->assertHasNoErrors()
        ->call('addItem', $rootId)
        ->set('itemLabel', 'Winter collection')
        ->set('itemUrl', '/collections/winter')
        ->call('saveItem')
        ->assertHasNoErrors()
        ->call('addItem')
        ->set('itemLabel', 'About')
        ->set('itemType', 'page')
        ->set('itemResourceId', (string) Page::query()->create([
            'store_id' => $store->id,
            'title' => 'About',
            'handle' => 'about',
            'status' => 'published',
        ])->id)
        ->call('saveItem')
        ->assertHasNoErrors();

    $aboutId = $component->get('menuItems')[1]['id'];
    $childIds = array_column($component->get('menuItems')[0]['children'], 'id');

    $component->call('editItem', $aboutId)
        ->set('itemLabel', 'About our store')
        ->call('saveItem')
        ->call('reorderItems', ['root' => [$aboutId, $rootId], $rootId => [$childIds[1], $childIds[0]]])
        ->call('reorderItems', $aboutId, 0, 'root')
        ->call('saveMenu')
        ->assertHasNoErrors();

    $footerMenu = NavigationMenu::query()->where('store_id', $store->id)->where('handle', 'footer-menu')->firstOrFail();
    $component->call('selectMenu', $footerMenu->id)
        ->call('addItem')
        ->set('itemLabel', 'Footer help')
        ->set('itemUrl', '/pages/help')
        ->call('saveItem')
        ->call('saveMenu')
        ->assertHasNoErrors();

    $savedRoot = NavigationItem::query()->where('menu_id', $mainMenu->id)->whereNull('parent_id')->where('label', 'Departments')->firstOrFail();
    $savedChild = NavigationItem::query()->where('menu_id', $mainMenu->id)->where('parent_id', $savedRoot->id)->where('label', 'Summer collection')->firstOrFail();

    expect($savedRoot->position)->toBe(1)
        ->and($savedChild->position)->toBe(1)
        ->and($savedChild->label)->toBe('Summer collection')
        ->and($savedRoot->children()->orderBy('position')->pluck('label')->all())->toBe(['Winter collection', 'Summer collection'])
        ->and(NavigationItem::query()->where('menu_id', $mainMenu->id)->whereNull('parent_id')->orderBy('position')->value('label'))->toBe('About our store');

    $this->get('http://navigation-menu.test/')
        ->assertOk()
        ->assertSee('Departments')
        ->assertSee('Summer collection')
        ->assertSee('About our store')
        ->assertSee('/pages/about')
        ->assertSee('Footer help')
        ->assertSee('/pages/help');
});

it('allows an admin to clear and save an empty menu', function () {
    $store = shopStore('navigation-empty.test');
    $user = navigationAdmin($store);
    app()->instance('current_store', $store);
    $component = Livewire::actingAs($user)->test(NavigationIndex::class);
    $mainMenu = NavigationMenu::query()->where('store_id', $store->id)->where('handle', 'main-menu')->firstOrFail();

    $component->call('addItem')
        ->set('itemLabel', 'Temporary link')
        ->set('itemUrl', '/temporary')
        ->call('saveItem')
        ->call('saveMenu');

    $itemId = $component->get('menuItems')[0]['id'];
    $component->call('removeItem', $itemId)->call('saveMenu')->assertHasNoErrors();

    expect(NavigationItem::query()->where('menu_id', $mainMenu->id)->count())->toBe(0);
});

it('rejects cross-store menus and resource references', function () {
    $store = shopStore('navigation-scope.test');
    $otherStore = shopStore('navigation-scope-other.test');
    $user = User::factory()->create();
    $user->stores()->attach([$store->id => ['role' => 'owner'], $otherStore->id => ['role' => 'owner']]);
    app()->instance('current_store', $store);
    $foreignMenu = NavigationMenu::query()->create(['store_id' => $otherStore->id, 'handle' => 'private-menu', 'title' => 'Private']);
    $foreignPage = Page::query()->create(['store_id' => $otherStore->id, 'title' => 'Private page', 'handle' => 'private', 'status' => 'published']);

    expect(fn () => Livewire::actingAs($user)->test(NavigationIndex::class)->call('selectMenu', $foreignMenu->id))
        ->toThrow(ModelNotFoundException::class);

    $mainMenu = NavigationMenu::query()->where('store_id', $store->id)->where('handle', 'main-menu')->firstOrFail();

    $component = Livewire::actingAs($user)->test(NavigationIndex::class)
        ->call('selectMenu', $mainMenu->id)
        ->call('addItem')
        ->set('itemLabel', 'Foreign page')
        ->set('itemType', 'page')
        ->set('itemResourceId', (string) $foreignPage->id)
        ->call('saveItem')
        ->assertHasErrors('itemResourceId');

    $component->set('menuItems', [[
        'id' => 'forged-item',
        'label' => 'Foreign page',
        'type' => 'page',
        'url' => null,
        'resource_id' => (string) $foreignPage->id,
        'children' => [],
    ]])->call('saveMenu')->assertHasErrors('menuItems.0.resource_id');

    expect(NavigationItem::query()->where('menu_id', $mainMenu->id)->count())->toBe(0);
});

it('rejects unsafe menu URLs and unauthorized navigation access', function () {
    $store = shopStore('navigation-security.test');
    $owner = navigationAdmin($store);
    $staff = navigationAdmin($store, 'staff');
    app()->instance('current_store', $store);
    $mainMenu = NavigationMenu::query()->create(['store_id' => $store->id, 'handle' => 'main-menu', 'title' => 'Main Menu']);

    Livewire::actingAs($owner)->test(NavigationIndex::class)
        ->call('selectMenu', $mainMenu->id)
        ->call('addItem')
        ->set('itemLabel', 'Unsafe')
        ->set('itemUrl', 'javascript:alert(1)')
        ->call('saveItem')
        ->assertHasErrors('itemUrl');

    $this->actingAs($staff)
        ->withSession(['current_store_id' => $store->id])
        ->get(route('admin.navigation'))
        ->assertForbidden();
});
