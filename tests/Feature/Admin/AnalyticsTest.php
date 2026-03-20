<?php

use App\Livewire\Admin\Analytics\Index;
use App\Models\AnalyticsDaily;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->user = $this->context['user'];
    $this->actingAs($this->user);
    session()->put('current_store_id', $this->store->id);
});

it('renders the analytics page for an authenticated admin', function () {
    $response = $this->get('/admin/analytics');

    $response->assertSuccessful();
    $response->assertSee('Analytics');
});

it('displays KPI tiles from analytics_daily', function () {
    AnalyticsDaily::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'date' => now()->format('Y-m-d'),
        'orders_count' => 10,
        'revenue_amount' => 50000,
        'aov_amount' => 5000,
        'visits_count' => 200,
        'add_to_cart_count' => 30,
        'checkout_started_count' => 15,
        'checkout_completed_count' => 10,
    ]);

    Livewire::test(Index::class)
        ->assertSee('Total Sales')
        ->assertSee('Orders')
        ->assertSee('Avg Order Value')
        ->assertSee('Conversion Rate')
        ->assertSee('$500.00')
        ->assertSee('10')
        ->assertSee('5.0%');
});

it('displays sales chart data', function () {
    AnalyticsDaily::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'date' => now()->format('Y-m-d'),
        'orders_count' => 5,
        'revenue_amount' => 25000,
        'aov_amount' => 5000,
        'visits_count' => 100,
        'add_to_cart_count' => 20,
        'checkout_started_count' => 10,
        'checkout_completed_count' => 5,
    ]);

    Livewire::test(Index::class)
        ->assertSee('Daily Revenue')
        ->assertSee('$250.00');
});

it('filters by date range', function () {
    AnalyticsDaily::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'date' => now()->format('Y-m-d'),
        'orders_count' => 3,
        'revenue_amount' => 15000,
        'aov_amount' => 5000,
        'visits_count' => 50,
        'add_to_cart_count' => 10,
        'checkout_started_count' => 5,
        'checkout_completed_count' => 3,
    ]);

    $component = Livewire::test(Index::class);

    $component->set('dateRange', 'today')
        ->assertHasNoErrors()
        ->assertSee('$150.00');

    $component->set('dateRange', 'last_7_days')
        ->assertHasNoErrors();

    $component->set('dateRange', 'last_30_days')
        ->assertHasNoErrors();
});
