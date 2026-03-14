<?php

use App\Livewire\Admin\Analytics\Index;
use App\Models\Order;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ctx = createStoreContext();
});

it('requires authentication for analytics', function () {
    $this->get(route('admin.analytics.index'))
        ->assertRedirect(route('admin.login'));
});

it('renders the analytics page', function () {
    $this->actingAs($this->ctx['user']);

    $this->get(route('admin.analytics.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('shows order KPIs', function () {
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
        ->test(Index::class)
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
        ->test(Index::class)
        ->set('dateRange', 'today')
        ->assertSet('ordersCount', 1)
        ->assertSet('totalSales', 5000);
});

it('handles empty data', function () {
    Livewire::actingAs($this->ctx['user'])
        ->test(Index::class)
        ->assertSet('ordersCount', 0)
        ->assertSet('totalSales', 0)
        ->assertSet('averageOrderValue', 0);
});
