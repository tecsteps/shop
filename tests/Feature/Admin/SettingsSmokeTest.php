<?php

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders all admin settings pages', function (string $routeName, string $heading) {
    $user = User::factory()->create();
    $store = Store::factory()->create();
    $user->stores()->attach($store, ['role' => StoreUserRole::Owner]);

    $this->actingAs($user)->withSession(['current_store_id' => $store->id])->get(route($routeName))
        ->assertSuccessful()->assertSee($heading);
})->with([
    'general' => ['admin.settings.index', 'Settings'],
    'shipping' => ['admin.settings.shipping', 'Shipping'],
    'taxes' => ['admin.settings.taxes', 'Taxes'],
]);

it('renders admin section pages', function (string $routeName, string $heading) {
    $user = User::factory()->create();
    $store = Store::factory()->create();
    $user->stores()->attach($store, ['role' => StoreUserRole::Owner]);

    $this->actingAs($user)->withSession(['current_store_id' => $store->id])->get(route($routeName))
        ->assertSuccessful()->assertSee($heading);
})->with([
    'dashboard' => ['admin.dashboard', 'Dashboard'],
    'products' => ['admin.products.index', 'Products'],
    'create product' => ['admin.products.create', 'Add product'],
    'collections' => ['admin.collections.index', 'Collections'],
    'create collection' => ['admin.collections.create', 'Add collection'],
    'inventory' => ['admin.inventory.index', 'Inventory'],
    'orders' => ['admin.orders.index', 'Orders'],
    'customers' => ['admin.customers.index', 'Customers'],
    'discounts' => ['admin.discounts.index', 'Discounts'],
    'create discount' => ['admin.discounts.create', 'Create discount'],
    'pages' => ['admin.pages.index', 'Pages'],
    'create page' => ['admin.pages.create', 'Add page'],
    'themes' => ['admin.themes.index', 'Themes'],
    'navigation' => ['admin.navigation.index', 'Navigation'],
    'analytics' => ['admin.analytics.index', 'Analytics'],
    'apps' => ['admin.apps.index', 'Apps'],
    'developers' => ['admin.developers.index', 'Developers'],
]);
