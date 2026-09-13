<?php

use App\Enums\StoreUserRole;
use App\Models\StoreUser;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('redirects guests to the admin login on every admin page', function () {
    $routes = [
        'admin.dashboard',
        'admin.products.index',
        'admin.inventory.index',
        'admin.collections.index',
        'admin.orders.index',
        'admin.customers.index',
        'admin.discounts.index',
        'admin.settings.index',
        'admin.themes.index',
        'admin.pages.index',
        'admin.navigation.index',
        'admin.apps.index',
        'admin.developers.index',
        'admin.analytics.index',
        'admin.search.settings',
    ];

    foreach ($routes as $route) {
        $this->get(route($route))->assertRedirect(route('admin.login'));
    }
});

it('denies access to users without a store membership', function () {
    ['store' => $store] = $this->createStoreContext();
    $stranger = User::factory()->create();
    $this->actingAsAdmin($stranger, $store);

    $this->get(route('admin.dashboard'))->assertForbidden();
});

it('denies support users access to owner-only sections', function () {
    ['store' => $store] = $this->createStoreContext();
    $support = User::factory()->create();
    StoreUser::factory()->create([
        'store_id' => $store->getKey(),
        'user_id' => $support->getKey(),
        'role' => StoreUserRole::Support,
    ]);
    $this->actingAsAdmin($support, $store);

    $this->get(route('admin.developers.index'))->assertForbidden();
    $this->get(route('admin.apps.index'))->assertForbidden();
    $this->get(route('admin.search.settings'))->assertForbidden();
});

it('allows support users read access to catalog and order sections', function () {
    ['store' => $store] = $this->createStoreContext();
    $support = User::factory()->create();
    StoreUser::factory()->create([
        'store_id' => $store->getKey(),
        'user_id' => $support->getKey(),
        'role' => StoreUserRole::Support,
    ]);
    $this->actingAsAdmin($support, $store);

    $this->get(route('admin.products.index'))->assertOk();
    $this->get(route('admin.orders.index'))->assertOk();
    $this->get(route('admin.customers.index'))->assertOk();
});
