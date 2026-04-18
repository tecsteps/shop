<?php

use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Livewire\Admin\Dashboard;
use App\Models\Order;
use Livewire\Livewire;

it('shows dashboard metrics for paid orders in range', function (): void {
    $ctx = $this->createStoreContext(['hostname' => 'dash-metrics.test']);

    Order::factory()->count(3)->create([
        'store_id' => $ctx['store']->id,
        'total_amount' => 5000,
        'status' => OrderStatus::Paid,
        'financial_status' => FinancialStatus::Paid,
        'placed_at' => now()->subDays(2),
    ]);

    Order::factory()->create([
        'store_id' => $ctx['store']->id,
        'total_amount' => 9999,
        'status' => OrderStatus::Pending,
        'financial_status' => FinancialStatus::Pending,
        'placed_at' => now()->subDays(2),
    ]);

    $this->actingAsAdmin($ctx['owner'], $ctx['store']);

    Livewire::test(Dashboard::class)
        ->assertSee('Dashboard')
        ->assertViewHas('metrics', function ($metrics) {
            return $metrics['order_count'] === 3
                && $metrics['sales_amount'] === 15000
                && $metrics['aov_amount'] === 5000;
        });
});

it('renders recent orders in the dashboard', function (): void {
    $ctx = $this->createStoreContext(['hostname' => 'dash-recent.test']);

    Order::factory()->paid()->create([
        'store_id' => $ctx['store']->id,
        'email' => 'buyer@example.com',
        'order_number' => '2001',
        'placed_at' => now(),
    ]);

    $this->actingAsAdmin($ctx['owner'], $ctx['store']);

    Livewire::test(Dashboard::class)
        ->assertSee('#2001')
        ->assertSee('buyer@example.com');
});
