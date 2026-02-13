<?php

use App\Jobs\AggregateAnalytics;
use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
});

test('aggregates daily metrics from events', function () {
    $date = '2025-06-15';
    $timestamp = Carbon::parse($date)->setHour(10);

    // Create various events for the day
    AnalyticsEvent::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'event_type' => 'page_view',
        'session_id' => 'session-1',
        'properties_json' => [],
        'created_at' => $timestamp,
    ]);

    AnalyticsEvent::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'event_type' => 'page_view',
        'session_id' => 'session-2',
        'properties_json' => [],
        'created_at' => $timestamp,
    ]);

    AnalyticsEvent::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'event_type' => 'add_to_cart',
        'session_id' => 'session-1',
        'properties_json' => ['product_id' => 1],
        'created_at' => $timestamp,
    ]);

    AnalyticsEvent::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'event_type' => 'checkout_started',
        'session_id' => 'session-1',
        'properties_json' => [],
        'created_at' => $timestamp,
    ]);

    AnalyticsEvent::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'event_type' => 'checkout_completed',
        'session_id' => 'session-1',
        'properties_json' => ['order_id' => 1, 'total_amount' => 5000],
        'created_at' => $timestamp,
    ]);

    (new AggregateAnalytics($date))->handle();

    $daily = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->whereDate('date', $date)
        ->first();

    expect($daily)->not->toBeNull();
    expect($daily->visits_count)->toBe(2);
    expect($daily->add_to_cart_count)->toBe(1);
    expect($daily->checkout_started_count)->toBe(1);
    expect($daily->checkout_completed_count)->toBe(1);
});

test('calculates correct order count and revenue', function () {
    $date = '2025-06-15';
    $timestamp = Carbon::parse($date)->setHour(14);

    AnalyticsEvent::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'event_type' => 'checkout_completed',
        'session_id' => 'session-1',
        'properties_json' => ['order_id' => 1, 'total_amount' => 3000],
        'created_at' => $timestamp,
    ]);

    AnalyticsEvent::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'event_type' => 'checkout_completed',
        'session_id' => 'session-2',
        'properties_json' => ['order_id' => 2, 'total_amount' => 7000],
        'created_at' => $timestamp,
    ]);

    (new AggregateAnalytics($date))->handle();

    $daily = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->whereDate('date', $date)
        ->first();

    expect($daily->orders_count)->toBe(2);
    expect($daily->revenue_amount)->toBe(10000);
    expect($daily->aov_amount)->toBe(5000);
});

test('handles stores with no events', function () {
    $date = '2025-06-15';

    (new AggregateAnalytics($date))->handle();

    $daily = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->whereDate('date', $date)
        ->first();

    expect($daily)->not->toBeNull();
    expect($daily->orders_count)->toBe(0);
    expect($daily->revenue_amount)->toBe(0);
    expect($daily->visits_count)->toBe(0);
});

test('is idempotent running twice gives same result', function () {
    $date = '2025-06-15';
    $timestamp = Carbon::parse($date)->setHour(10);

    AnalyticsEvent::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'event_type' => 'checkout_completed',
        'session_id' => 'session-1',
        'properties_json' => ['order_id' => 1, 'total_amount' => 5000],
        'created_at' => $timestamp,
    ]);

    AnalyticsEvent::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'event_type' => 'page_view',
        'session_id' => 'session-1',
        'properties_json' => [],
        'created_at' => $timestamp,
    ]);

    // Run twice
    (new AggregateAnalytics($date))->handle();
    (new AggregateAnalytics($date))->handle();

    $dailyRecords = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->whereDate('date', $date)
        ->get();

    expect($dailyRecords)->toHaveCount(1);
    expect($dailyRecords->first()->orders_count)->toBe(1);
    expect($dailyRecords->first()->revenue_amount)->toBe(5000);
    expect($dailyRecords->first()->visits_count)->toBe(1);
});
