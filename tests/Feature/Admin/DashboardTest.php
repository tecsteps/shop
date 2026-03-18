<?php

use App\Livewire\Admin\Dashboard;
use App\Models\Order;
use Livewire\Livewire;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->actingAs($this->ctx['user']);
    session(['current_store_id' => $this->ctx['store']->id]);
});

it('requires authentication to access the dashboard', function () {
    auth()->logout();
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('renders the dashboard page for authenticated admin', function () {
    $this->get('/admin')
        ->assertStatus(200)
        ->assertSee('Dashboard');
});

it('shows KPI tiles with correct data', function () {
    Order::factory()->count(3)->create([
        'store_id' => $this->ctx['store']->id,
        'total_amount' => 5000,
        'placed_at' => now(),
    ]);

    $component = Livewire::test(Dashboard::class);

    $component->assertSee('Total Sales');
    $component->assertSee('Orders');
    expect($component->get('ordersCount'))->toBe(3);
    expect($component->get('totalSales'))->toBe(15000);
    expect($component->get('averageOrderValue'))->toBe(5000);
});

it('supports date range filtering', function () {
    Order::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'total_amount' => 10000,
        'placed_at' => now(),
    ]);

    Order::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'total_amount' => 5000,
        'placed_at' => now()->subDays(40),
    ]);

    $component = Livewire::test(Dashboard::class);

    expect($component->get('totalSales'))->toBe(10000);
    expect($component->get('ordersCount'))->toBe(1);

    $component->set('dateRange', 'today');
    expect($component->get('ordersCount'))->toBe(1);
});

it('shows recent orders table', function () {
    Order::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'order_number' => '#5001',
        'placed_at' => now(),
    ]);

    $component = Livewire::test(Dashboard::class);

    $component->assertSee('#5001');
    $component->assertSee('Recent orders');
});

it('shows empty state when no orders exist', function () {
    $component = Livewire::test(Dashboard::class);

    $component->assertSee('No orders yet');
    expect($component->get('ordersCount'))->toBe(0);
});

it('supports custom date range', function () {
    Order::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'total_amount' => 7000,
        'placed_at' => now()->subDays(5),
    ]);

    $component = Livewire::test(Dashboard::class);

    $component->set('dateRange', 'custom');
    $component->set('customStartDate', now()->subDays(10)->format('Y-m-d'));
    $component->set('customEndDate', now()->format('Y-m-d'));

    expect($component->get('ordersCount'))->toBe(1);
    expect($component->get('totalSales'))->toBe(7000);
});

it('calculates percentage changes correctly', function () {
    Order::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'total_amount' => 10000,
        'placed_at' => now()->subDays(5),
    ]);

    Order::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'total_amount' => 5000,
        'placed_at' => now()->subDays(35),
    ]);

    $component = Livewire::test(Dashboard::class);

    expect($component->get('salesChange'))->toBe(100.0);
});
