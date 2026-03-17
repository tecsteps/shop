<?php

use App\Livewire\Admin\Dashboard;
use App\Models\Order;
use Livewire\Livewire;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->store = $this->ctx['store'];
    $this->user = $this->ctx['user'];
    $this->session = ['store_id' => $this->store->id, 'current_store_id' => $this->store->id];
});

it('restricts dashboard access to unauthenticated users', function () {
    $this->get(route('admin.dashboard'))
        ->assertRedirect(route('admin.login'));
});

it('shows KPI tiles with correct sales data', function () {
    Order::factory()->count(3)->create([
        'store_id' => $this->store->id,
        'total_amount' => 5000,
        'placed_at' => now()->subDays(5),
    ]);

    session($this->session);

    $component = Livewire::actingAs($this->user)
        ->test(Dashboard::class);

    $component->assertSet('ordersCount', 3)
        ->assertSet('totalSales', 15000)
        ->assertSet('averageOrderValue', 5000)
        ->assertSee('Total Sales')
        ->assertSee('Orders');
});

it('calculates percentage change compared to previous period', function () {
    Order::factory()->count(2)->create([
        'store_id' => $this->store->id,
        'total_amount' => 5000,
        'placed_at' => now()->subDays(5),
    ]);

    Order::factory()->create([
        'store_id' => $this->store->id,
        'total_amount' => 3000,
        'placed_at' => now()->subDays(35),
    ]);

    session($this->session);

    $component = Livewire::actingAs($this->user)
        ->test(Dashboard::class);

    $component->assertSet('ordersCount', 2);

    expect($component->get('salesChange'))->toBeGreaterThan(0);
});

it('filters KPI data by date range', function () {
    Order::factory()->create([
        'store_id' => $this->store->id,
        'total_amount' => 3000,
        'placed_at' => now(),
    ]);

    Order::factory()->create([
        'store_id' => $this->store->id,
        'total_amount' => 7000,
        'placed_at' => now()->subDays(60),
    ]);

    session($this->session);

    $component = Livewire::actingAs($this->user)
        ->test(Dashboard::class);

    // Default last_30_days includes only the recent order
    $component->assertSet('ordersCount', 1)
        ->assertSet('totalSales', 3000);

    // Switch to today
    $component->set('dateRange', 'today');

    $component->assertSet('ordersCount', 1)
        ->assertSet('totalSales', 3000);
});
