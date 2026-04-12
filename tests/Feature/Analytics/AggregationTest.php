<?php

use App\Jobs\AggregateAnalytics;
use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Order;
use App\Models\Store;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
    $this->date = CarbonImmutable::parse('2026-04-10');
});

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

it('aggregates raw events into a daily row', function (): void {
    $at = $this->date->setTime(12, 0, 0);

    Order::factory()->for($this->store)->create([
        'placed_at' => $at,
        'total_amount' => 5000,
    ]);
    Order::factory()->for($this->store)->create([
        'placed_at' => $at,
        'total_amount' => 3000,
    ]);

    AnalyticsEvent::factory()->for($this->store)->create([
        'type' => 'page_view',
        'session_id' => 'sess-1',
        'occurred_at' => $at,
    ]);
    AnalyticsEvent::factory()->for($this->store)->create([
        'type' => 'page_view',
        'session_id' => 'sess-2',
        'occurred_at' => $at,
    ]);
    AnalyticsEvent::factory()->for($this->store)->create([
        'type' => 'page_view',
        'session_id' => 'sess-1',
        'occurred_at' => $at,
    ]);
    AnalyticsEvent::factory()->for($this->store)->create([
        'type' => 'add_to_cart',
        'session_id' => 'sess-1',
        'occurred_at' => $at,
    ]);
    AnalyticsEvent::factory()->for($this->store)->create([
        'type' => 'checkout_started',
        'session_id' => 'sess-1',
        'occurred_at' => $at,
    ]);
    AnalyticsEvent::factory()->for($this->store)->create([
        'type' => 'checkout_completed',
        'session_id' => 'sess-1',
        'occurred_at' => $at,
    ]);

    (new AggregateAnalytics($this->date->toDateString()))->handle();

    $daily = AnalyticsDaily::query()
        ->where('store_id', $this->store->id)
        ->where('date', $this->date->toDateString())
        ->first();

    expect($daily)->not->toBeNull()
        ->and((int) $daily->orders_count)->toBe(2)
        ->and((int) $daily->revenue_amount)->toBe(8000)
        ->and((int) $daily->aov_amount)->toBe(4000)
        ->and((int) $daily->visits_count)->toBe(2)
        ->and((int) $daily->add_to_cart_count)->toBe(1)
        ->and((int) $daily->checkout_started_count)->toBe(1)
        ->and((int) $daily->checkout_completed_count)->toBe(1);
});

it('handles zero events for a store gracefully', function (): void {
    (new AggregateAnalytics($this->date->toDateString()))->handle();

    $daily = AnalyticsDaily::query()
        ->where('store_id', $this->store->id)
        ->where('date', $this->date->toDateString())
        ->first();

    expect($daily)->not->toBeNull()
        ->and((int) $daily->orders_count)->toBe(0)
        ->and((int) $daily->revenue_amount)->toBe(0)
        ->and((int) $daily->aov_amount)->toBe(0)
        ->and((int) $daily->visits_count)->toBe(0);
});
