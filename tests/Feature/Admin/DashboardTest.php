<?php

use App\Enums\StoreUserRole;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function createAdminUser(Store $store, StoreUserRole $role = StoreUserRole::Owner): User
{
    $user = User::factory()->create();
    $store->users()->attach($user, ['role' => $role->value]);

    return $user;
}

function bindStore(Store $store): void
{
    app()->instance('current_store', $store);
    session()->put('current_store_id', $store->id);
}

it('shows dashboard with KPI tiles', function () {
    $store = Store::factory()->create();
    $user = createAdminUser($store);
    bindStore($store);

    Order::factory()->create([
        'store_id' => $store->id,
        'total_amount' => 5000,
        'placed_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('Dashboard');
    $response->assertSee('Total Sales');
    $response->assertSee('Orders');
    $response->assertSee('Avg. Order Value');
});

it('shows recent orders on dashboard', function () {
    $store = Store::factory()->create();
    $user = createAdminUser($store);
    bindStore($store);

    $order = Order::factory()->create([
        'store_id' => $store->id,
        'order_number' => '#9001',
        'placed_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('#9001');
});

it('requires authentication for dashboard', function () {
    $response = $this->get(route('admin.dashboard'));

    $response->assertRedirect();
});

it('filters dashboard by date range', function () {
    $store = Store::factory()->create();
    $user = createAdminUser($store);
    bindStore($store);

    Order::factory()->create([
        'store_id' => $store->id,
        'total_amount' => 3000,
        'placed_at' => now()->subDays(2),
    ]);

    Order::factory()->create([
        'store_id' => $store->id,
        'total_amount' => 5000,
        'placed_at' => now()->subDays(10),
    ]);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Admin\Dashboard::class)
        ->set('dateRange', '7d')
        ->assertSee('$30.00')
        ->assertDontSee('$80.00');
});

it('only shows data for current store', function () {
    $store = Store::factory()->create();
    $otherStore = Store::factory()->create();
    $user = createAdminUser($store);
    bindStore($store);

    Order::factory()->create([
        'store_id' => $store->id,
        'order_number' => '#1001',
        'total_amount' => 5000,
        'placed_at' => now(),
    ]);

    Order::factory()->create([
        'store_id' => $otherStore->id,
        'order_number' => '#2001',
        'total_amount' => 8000,
        'placed_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Admin\Dashboard::class)
        ->assertSee('#1001')
        ->assertDontSee('#2001');
});
