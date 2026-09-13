<?php

use App\Livewire\Admin\Dashboard;
use App\Models\AnalyticsDaily;
use App\Models\Order;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    ['store' => $this->store, 'user' => $this->user] = $this->createStoreContext();
    $this->actingAsAdmin($this->user, $this->store);
});

it('renders the dashboard for an authenticated store member', function () {
    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSeeLivewire(Dashboard::class);
});

it('redirects guests to the admin login', function () {
    auth()->logout();

    $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
});

it('shows kpis computed from orders and daily metrics', function () {
    Order::factory()->create([
        'store_id' => $this->store->getKey(),
        'total_amount' => 5000,
        'placed_at' => now(),
    ]);
    AnalyticsDaily::factory()->create([
        'store_id' => $this->store->getKey(),
        'date' => today()->toDateString(),
        'visits_count' => 100,
    ]);

    Livewire::test(Dashboard::class)
        ->assertViewHas('ordersCount', 1)
        ->assertViewHas('totalSales', 5000)
        ->assertViewHas('visitorsCount', 100);
});

it('filters kpis by date range', function () {
    Order::factory()->create([
        'store_id' => $this->store->getKey(),
        'total_amount' => 5000,
        'placed_at' => now()->subDays(20),
    ]);

    Livewire::test(Dashboard::class)
        ->set('dateRange', 'today')
        ->assertViewHas('ordersCount', 0)
        ->set('dateRange', 'last_30_days')
        ->assertViewHas('ordersCount', 1);
});

it('only counts orders from the current store', function () {
    ['store' => $otherStore] = $this->createStoreContext();
    Order::factory()->create(['store_id' => $otherStore->getKey(), 'placed_at' => now()]);
    $this->actingAsAdmin($this->user, $this->store);

    Livewire::test(Dashboard::class)->assertViewHas('ordersCount', 0);
});
