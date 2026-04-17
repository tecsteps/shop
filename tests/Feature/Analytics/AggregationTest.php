<?php

use App\Jobs\AggregateAnalytics;
use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Store;
use App\Services\AnalyticsService;

beforeEach(function () {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
    $this->analyticsService = app(AnalyticsService::class);
});

it('aggregates events into daily metrics', function () {
    $date = '2026-03-19';

    // Create raw events for the date
    AnalyticsEvent::factory()->count(3)->create([
        'store_id' => $this->store->id,
        'type' => 'page_view',
        'session_id' => 'session-1',
        'created_at' => "{$date} 10:00:00",
    ]);

    AnalyticsEvent::factory()->create([
        'store_id' => $this->store->id,
        'type' => 'page_view',
        'session_id' => 'session-2',
        'created_at' => "{$date} 11:00:00",
    ]);

    AnalyticsEvent::factory()->count(2)->create([
        'store_id' => $this->store->id,
        'type' => 'add_to_cart',
        'created_at' => "{$date} 12:00:00",
    ]);

    AnalyticsEvent::factory()->create([
        'store_id' => $this->store->id,
        'type' => 'checkout_started',
        'created_at' => "{$date} 13:00:00",
    ]);

    AnalyticsEvent::factory()->create([
        'store_id' => $this->store->id,
        'type' => 'checkout_completed',
        'properties_json' => ['order_total' => 5000],
        'created_at' => "{$date} 14:00:00",
    ]);

    AnalyticsEvent::factory()->create([
        'store_id' => $this->store->id,
        'type' => 'checkout_completed',
        'properties_json' => ['order_total' => 3000],
        'created_at' => "{$date} 15:00:00",
    ]);

    $job = new AggregateAnalytics($date);
    $job->handle();

    $daily = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('date', $date)
        ->first();

    expect($daily)->not->toBeNull()
        ->and($daily->orders_count)->toBe(2)
        ->and($daily->revenue_amount)->toBe(8000)
        ->and($daily->aov_amount)->toBe(4000)
        ->and($daily->visits_count)->toBe(2)
        ->and($daily->add_to_cart_count)->toBe(2)
        ->and($daily->checkout_started_count)->toBe(1)
        ->and($daily->checkout_completed_count)->toBe(2);
});

it('handles days with no events gracefully', function () {
    $job = new AggregateAnalytics('2026-03-18');
    $job->handle();

    $count = AnalyticsDaily::withoutGlobalScopes()->where('store_id', $this->store->id)->count();
    expect($count)->toBe(0);
});

it('updates existing daily record on re-aggregation', function () {
    $date = '2026-03-19';

    AnalyticsEvent::factory()->create([
        'store_id' => $this->store->id,
        'type' => 'checkout_completed',
        'properties_json' => ['order_total' => 2000],
        'created_at' => "{$date} 10:00:00",
    ]);

    $job = new AggregateAnalytics($date);
    $job->handle();

    $daily = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('date', $date)
        ->first();

    expect($daily->orders_count)->toBe(1)
        ->and($daily->revenue_amount)->toBe(2000);

    // Add another event and re-aggregate
    AnalyticsEvent::factory()->create([
        'store_id' => $this->store->id,
        'type' => 'checkout_completed',
        'properties_json' => ['order_total' => 3000],
        'created_at' => "{$date} 11:00:00",
    ]);

    $job = new AggregateAnalytics($date);
    $job->handle();

    $updatedDaily = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('date', $date)
        ->first();

    expect($updatedDaily->orders_count)->toBe(2)
        ->and($updatedDaily->revenue_amount)->toBe(5000)
        ->and($updatedDaily->aov_amount)->toBe(2500);
});

it('scopes aggregation to each store separately', function () {
    $otherStore = Store::factory()->create();
    $date = '2026-03-19';

    AnalyticsEvent::factory()->create([
        'store_id' => $this->store->id,
        'type' => 'checkout_completed',
        'properties_json' => ['order_total' => 1000],
        'created_at' => "{$date} 10:00:00",
    ]);

    AnalyticsEvent::factory()->create([
        'store_id' => $otherStore->id,
        'type' => 'checkout_completed',
        'properties_json' => ['order_total' => 9000],
        'created_at' => "{$date} 10:00:00",
    ]);

    $job = new AggregateAnalytics($date);
    $job->handle();

    $daily1 = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('date', $date)
        ->first();

    $daily2 = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $otherStore->id)
        ->where('date', $date)
        ->first();

    expect($daily1->revenue_amount)->toBe(1000)
        ->and($daily2->revenue_amount)->toBe(9000);
});

it('returns daily metrics for a date range', function () {
    AnalyticsDaily::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'date' => '2026-03-17',
        'orders_count' => 5,
        'revenue_amount' => 25000,
        'aov_amount' => 5000,
        'visits_count' => 100,
        'add_to_cart_count' => 20,
        'checkout_started_count' => 10,
        'checkout_completed_count' => 5,
    ]);

    AnalyticsDaily::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'date' => '2026-03-18',
        'orders_count' => 3,
        'revenue_amount' => 15000,
        'aov_amount' => 5000,
        'visits_count' => 80,
        'add_to_cart_count' => 15,
        'checkout_started_count' => 8,
        'checkout_completed_count' => 3,
    ]);

    $metrics = $this->analyticsService->getDailyMetrics($this->store, '2026-03-17', '2026-03-18');

    expect($metrics)->toHaveCount(2)
        ->and($metrics->first()->date)->toBe('2026-03-17')
        ->and($metrics->last()->date)->toBe('2026-03-18');
});

it('calculates zero aov when no orders exist', function () {
    $date = '2026-03-19';

    AnalyticsEvent::factory()->create([
        'store_id' => $this->store->id,
        'type' => 'page_view',
        'session_id' => 'session-1',
        'created_at' => "{$date} 10:00:00",
    ]);

    $job = new AggregateAnalytics($date);
    $job->handle();

    $daily = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('date', $date)
        ->first();

    expect($daily->orders_count)->toBe(0)
        ->and($daily->aov_amount)->toBe(0)
        ->and($daily->visits_count)->toBe(1);
});
