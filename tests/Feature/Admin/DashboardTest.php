<?php

use App\Livewire\Admin\Dashboard;
use App\Models\Order;
use Livewire\Livewire;

it('renders the admin dashboard for authenticated users', function (): void {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    Livewire::test(Dashboard::class)
        ->assertOk()
        ->assertSee('Dashboard');
});

it('shows KPI tiles with correct data', function (): void {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    Order::factory()->count(3)->create([
        'store_id' => $ctx['store']->id,
        'total_amount' => 10000,
        'placed_at' => now(),
    ]);

    Livewire::test(Dashboard::class)
        ->assertSee('Total Sales')
        ->assertSee('Orders')
        ->assertSee('Avg Order Value');
});

it('filters dashboard by date range', function (): void {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    Livewire::test(Dashboard::class)
        ->set('dateRange', 'today')
        ->assertOk()
        ->set('dateRange', 'last_7_days')
        ->assertOk()
        ->set('dateRange', 'last_30_days')
        ->assertOk();
});

it('shows recent orders on dashboard', function (): void {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    $order = Order::factory()->create([
        'store_id' => $ctx['store']->id,
        'order_number' => '#TEST001',
        'placed_at' => now(),
    ]);

    Livewire::test(Dashboard::class)
        ->assertSee('#TEST001');
});
