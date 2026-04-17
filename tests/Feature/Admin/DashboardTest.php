<?php

use App\Livewire\Admin\Dashboard;
use App\Models\Order;
use Livewire\Livewire;

it('renders the admin dashboard', function () {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    Livewire::test(Dashboard::class)
        ->assertOk()
        ->assertSee('Dashboard');
});

it('shows KPI tiles with correct data', function () {
    $ctx = createStoreContext();

    Order::factory()->count(3)->create([
        'store_id' => $ctx['store']->id,
        'total_amount' => 10000,
        'placed_at' => now(),
    ]);

    actingAsAdmin($ctx['user'], $ctx['store']);

    Livewire::test(Dashboard::class)
        ->assertSee('Total Sales')
        ->assertSee('Orders')
        ->assertSee('Average Order Value');
});

it('restricts dashboard to authenticated admins', function () {
    $this->get('/admin/dashboard')
        ->assertRedirect();
});

it('filters metrics by date range', function () {
    $ctx = createStoreContext();

    Order::factory()->create([
        'store_id' => $ctx['store']->id,
        'total_amount' => 5000,
        'placed_at' => now()->subDays(5),
    ]);

    Order::factory()->create([
        'store_id' => $ctx['store']->id,
        'total_amount' => 8000,
        'placed_at' => now()->subDays(60),
    ]);

    actingAsAdmin($ctx['user'], $ctx['store']);

    Livewire::test(Dashboard::class)
        ->set('dateFrom', now()->subDays(10)->format('Y-m-d'))
        ->set('dateTo', now()->format('Y-m-d'))
        ->assertSee('$50.00');
});
