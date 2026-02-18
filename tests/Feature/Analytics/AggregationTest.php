<?php

use App\Jobs\AggregateAnalytics;
use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Order;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
});

it('aggregates daily metrics from raw events', function () {
    $date = '2025-01-15';

    AnalyticsEvent::factory()->count(5)->create([
        'store_id' => $this->store->id,
        'type' => 'page_view',
        'session_id' => 'session-1',
        'created_at' => $date.' 10:00:00',
    ]);

    AnalyticsEvent::factory()->count(3)->create([
        'store_id' => $this->store->id,
        'type' => 'add_to_cart',
        'created_at' => $date.' 11:00:00',
    ]);

    AnalyticsEvent::factory()->count(2)->create([
        'store_id' => $this->store->id,
        'type' => 'checkout_completed',
        'created_at' => $date.' 12:00:00',
    ]);

    AnalyticsEvent::factory()->create([
        'store_id' => $this->store->id,
        'type' => 'checkout_started',
        'created_at' => $date.' 11:30:00',
    ]);

    $job = new AggregateAnalytics($date);
    $job->handle();

    $daily = AnalyticsDaily::query()
        ->withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('date', $date)
        ->first();

    expect($daily)->not->toBeNull()
        ->and($daily->visits_count)->toBe(1)
        ->and($daily->add_to_cart_count)->toBe(3)
        ->and($daily->checkout_started_count)->toBe(1)
        ->and($daily->checkout_completed_count)->toBe(2);
});

it('calculates revenue and AOV correctly', function () {
    $date = '2025-01-15';

    Order::factory()->create([
        'store_id' => $this->store->id,
        'total_amount' => 1000,
        'placed_at' => $date.' 10:00:00',
        'status' => 'paid',
    ]);
    Order::factory()->create([
        'store_id' => $this->store->id,
        'total_amount' => 2000,
        'placed_at' => $date.' 11:00:00',
        'status' => 'paid',
    ]);
    Order::factory()->create([
        'store_id' => $this->store->id,
        'total_amount' => 3000,
        'placed_at' => $date.' 12:00:00',
        'status' => 'paid',
    ]);

    $job = new AggregateAnalytics($date);
    $job->handle();

    $daily = AnalyticsDaily::query()
        ->withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('date', $date)
        ->first();

    expect($daily)->not->toBeNull()
        ->and($daily->orders_count)->toBe(3)
        ->and($daily->revenue_amount)->toBe(6000)
        ->and($daily->aov_amount)->toBe(2000);
});

it('runs idempotently', function () {
    $date = '2025-01-15';

    AnalyticsEvent::factory()->count(3)->create([
        'store_id' => $this->store->id,
        'type' => 'page_view',
        'session_id' => 'session-1',
        'created_at' => $date.' 10:00:00',
    ]);

    Order::factory()->create([
        'store_id' => $this->store->id,
        'total_amount' => 5000,
        'placed_at' => $date.' 10:00:00',
        'status' => 'paid',
    ]);

    $job = new AggregateAnalytics($date);
    $job->handle();
    $job->handle();

    $dailyCount = AnalyticsDaily::query()
        ->withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('date', $date)
        ->count();

    $daily = AnalyticsDaily::query()
        ->withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('date', $date)
        ->first();

    expect($dailyCount)->toBe(1)
        ->and($daily->orders_count)->toBe(1)
        ->and($daily->revenue_amount)->toBe(5000);
});
