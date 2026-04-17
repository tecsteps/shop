<?php

use App\Livewire\Admin\Analytics\Index;
use App\Models\AnalyticsDaily;
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
    AnalyticsDaily::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'date' => now()->format('Y-m-d'),
        'orders_count' => 2,
        'revenue_amount' => 8000,
        'visits_count' => 100,
    ]);

    Livewire::actingAs($this->ctx['user'])
        ->test(Index::class)
        ->assertSet('ordersCount', 2)
        ->assertSet('totalSales', 8000)
        ->assertSet('averageOrderValue', 4000);
});

it('filters by date range', function () {
    AnalyticsDaily::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'date' => now()->format('Y-m-d'),
        'orders_count' => 1,
        'revenue_amount' => 5000,
        'visits_count' => 50,
    ]);

    AnalyticsDaily::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'date' => now()->subDays(60)->format('Y-m-d'),
        'orders_count' => 1,
        'revenue_amount' => 3000,
        'visits_count' => 30,
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
