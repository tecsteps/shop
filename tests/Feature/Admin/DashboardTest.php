<?php

use App\Models\Order;

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

    $this->actingAs($this->user);
    session($this->session);

    $dashboard = new \App\Livewire\Admin\Dashboard;
    $dashboard->mount();

    expect($dashboard->ordersCount)->toBe(3)
        ->and($dashboard->totalSales)->toBe(15000)
        ->and($dashboard->averageOrderValue)->toBe(5000);
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

    $this->actingAs($this->user);
    session($this->session);

    $dashboard = new \App\Livewire\Admin\Dashboard;
    $dashboard->mount();

    expect($dashboard->ordersCount)->toBe(2)
        ->and($dashboard->salesChange)->toBeGreaterThan(0);
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

    $this->actingAs($this->user);
    session($this->session);

    $dashboard = new \App\Livewire\Admin\Dashboard;
    $dashboard->mount();

    expect($dashboard->ordersCount)->toBe(1)
        ->and($dashboard->totalSales)->toBe(3000);

    $dashboard->dateRange = 'today';
    $dashboard->updatedDateRange();

    expect($dashboard->ordersCount)->toBe(1)
        ->and($dashboard->totalSales)->toBe(3000);
});
