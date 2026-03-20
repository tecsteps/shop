<?php

use App\Livewire\Admin\Dashboard;
use App\Models\AnalyticsDaily;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext();
    session()->put('current_store_id', $this->context['store']->id);
});

it('renders the admin dashboard', function () {
    $this->actingAs($this->context['user'])
        ->get('/admin')
        ->assertOk()
        ->assertSeeLivewire(Dashboard::class);
});

it('requires authentication for the admin dashboard', function () {
    $this->get('/admin')
        ->assertRedirect(route('login'));
});

it('displays KPI tiles with analytics data', function () {
    AnalyticsDaily::withoutGlobalScopes()->create([
        'store_id' => $this->context['store']->id,
        'date' => now()->format('Y-m-d'),
        'orders_count' => 10,
        'revenue_amount' => 50000,
        'aov_amount' => 5000,
        'visits_count' => 200,
        'add_to_cart_count' => 50,
        'checkout_started_count' => 20,
        'checkout_completed_count' => 10,
    ]);

    Livewire::actingAs($this->context['user'])
        ->test(Dashboard::class)
        ->assertSee('$500.00')
        ->assertSee('200');
});

it('supports date range filtering', function () {
    Livewire::actingAs($this->context['user'])
        ->test(Dashboard::class)
        ->assertSet('dateRange', 'last_30_days')
        ->set('dateRange', 'last_7_days')
        ->assertSet('dateRange', 'last_7_days')
        ->set('dateRange', 'today')
        ->assertSet('dateRange', 'today');
});

it('shows empty state when no data exists', function () {
    Livewire::actingAs($this->context['user'])
        ->test(Dashboard::class)
        ->assertSee('$0.00');
});
