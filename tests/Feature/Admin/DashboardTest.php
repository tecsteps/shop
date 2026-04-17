<?php

use App\Livewire\Admin\Dashboard;
use App\Models\Order;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->user = $this->context['user'];
});

it('renders admin dashboard for authenticated admin', function () {
    actingAsAdmin($this->user, $this->store)
        ->get(route('admin.dashboard'))
        ->assertOk();
});

it('displays KPI tiles with total sales and order count', function () {
    Order::factory()->count(3)->create([
        'store_id' => $this->store->id,
        'total_amount' => 5000,
        'placed_at' => now(),
    ]);

    Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->assertSet('ordersCount', 3)
        ->assertSet('totalSales', 15000);
});

it('shows recent orders table', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'order_number' => '#T1001',
        'placed_at' => now(),
    ]);

    Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->assertSee('#T1001');
});

it('filters KPIs by date range', function () {
    Order::factory()->create([
        'store_id' => $this->store->id,
        'total_amount' => 10000,
        'placed_at' => now(),
    ]);
    Order::factory()->create([
        'store_id' => $this->store->id,
        'total_amount' => 5000,
        'placed_at' => now()->subDays(10),
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->set('dateRange', 'last_7_days');

    expect($component->get('totalSales'))->toBe(10000);
});
