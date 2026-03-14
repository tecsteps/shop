<?php

use App\Livewire\Admin\Dashboard;
use App\Models\Order;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ctx = createStoreContext();
});

it('requires authentication', function () {
    $this->get(route('admin.dashboard'))
        ->assertRedirect(route('admin.login'));
});

it('renders the dashboard for authenticated users', function () {
    $this->actingAs($this->ctx['user']);

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSeeLivewire(Dashboard::class);
});

it('loads KPI data from orders', function () {
    Order::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'total_amount' => 5000,
        'placed_at' => now(),
    ]);

    Order::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'total_amount' => 3000,
        'placed_at' => now(),
    ]);

    Livewire::actingAs($this->ctx['user'])
        ->test(Dashboard::class)
        ->assertSet('ordersCount', 2)
        ->assertSet('totalSales', 8000)
        ->assertSet('averageOrderValue', 4000);
});

it('filters by date range', function () {
    Order::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'total_amount' => 5000,
        'placed_at' => now(),
    ]);

    Order::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'total_amount' => 3000,
        'placed_at' => now()->subDays(60),
    ]);

    Livewire::actingAs($this->ctx['user'])
        ->test(Dashboard::class)
        ->set('dateRange', 'today')
        ->assertSet('ordersCount', 1)
        ->assertSet('totalSales', 5000);
});

it('displays recent orders', function () {
    Order::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'order_number' => 'TEST-001',
        'email' => 'test@example.com',
        'total_amount' => 5000,
        'placed_at' => now(),
    ]);

    Livewire::actingAs($this->ctx['user'])
        ->test(Dashboard::class)
        ->assertSee('TEST-001')
        ->assertSee('test@example.com');
});
