<?php

use App\Livewire\Admin\Dashboard;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

it('redirects guests away from the admin dashboard', function (): void {
    $this->get('/admin')->assertRedirect();
});

it('renders the admin dashboard for an authenticated admin', function (): void {
    loginAsAdmin();

    $this->get('/admin')
        ->assertOk()
        ->assertSeeLivewire(Dashboard::class)
        ->assertSee('Dashboard');
});

it('computes KPIs from recent orders', function (): void {
    [$user, $store] = loginAsAdmin();

    Order::factory()->count(3)->create([
        'store_id' => $store->id,
        'total_amount' => 5000,
        'placed_at' => now()->subDays(2),
    ]);

    $component = Livewire::test(Dashboard::class)
        ->assertSet('period', '30d');

    $kpis = $component->instance()->kpis();

    expect($kpis['total_sales'])->toBe(15000)
        ->and($kpis['orders_count'])->toBe(3)
        ->and($kpis['aov'])->toBe(5000);
});
