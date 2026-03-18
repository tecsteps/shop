<?php

use App\Jobs\AggregateAnalytics;
use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Order;
use App\Services\AnalyticsService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->service = app(AnalyticsService::class);
});

it('aggregates page views into visits count by unique sessions', function () {
    $date = '2026-03-15';

    AnalyticsEvent::withoutGlobalScopes()->insert([
        ['store_id' => $this->store->id, 'type' => 'page_view', 'session_id' => 'sess-1', 'properties_json' => '{}', 'created_at' => $date.' 10:00:00'],
        ['store_id' => $this->store->id, 'type' => 'page_view', 'session_id' => 'sess-1', 'properties_json' => '{}', 'created_at' => $date.' 10:05:00'],
        ['store_id' => $this->store->id, 'type' => 'page_view', 'session_id' => 'sess-2', 'properties_json' => '{}', 'created_at' => $date.' 11:00:00'],
    ]);

    (new AggregateAnalytics($date))->handle();

    $daily = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('date', $date)
        ->first();

    expect($daily)->not->toBeNull()
        ->and($daily->visits_count)->toBe(2);
});

it('aggregates add_to_cart events', function () {
    $date = '2026-03-15';

    AnalyticsEvent::withoutGlobalScopes()->insert([
        ['store_id' => $this->store->id, 'type' => 'add_to_cart', 'session_id' => 'sess-1', 'properties_json' => '{}', 'created_at' => $date.' 10:00:00'],
        ['store_id' => $this->store->id, 'type' => 'add_to_cart', 'session_id' => 'sess-2', 'properties_json' => '{}', 'created_at' => $date.' 11:00:00'],
    ]);

    (new AggregateAnalytics($date))->handle();

    $daily = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('date', $date)
        ->first();

    expect($daily->add_to_cart_count)->toBe(2);
});

it('aggregates checkout started and completed events', function () {
    $date = '2026-03-15';

    AnalyticsEvent::withoutGlobalScopes()->insert([
        ['store_id' => $this->store->id, 'type' => 'checkout_started', 'session_id' => 'sess-1', 'properties_json' => '{}', 'created_at' => $date.' 10:00:00'],
        ['store_id' => $this->store->id, 'type' => 'checkout_started', 'session_id' => 'sess-2', 'properties_json' => '{}', 'created_at' => $date.' 11:00:00'],
        ['store_id' => $this->store->id, 'type' => 'checkout_completed', 'session_id' => 'sess-1', 'properties_json' => '{}', 'created_at' => $date.' 10:30:00'],
    ]);

    (new AggregateAnalytics($date))->handle();

    $daily = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('date', $date)
        ->first();

    expect($daily->checkout_started_count)->toBe(2)
        ->and($daily->checkout_completed_count)->toBe(1);
});

it('aggregates order revenue and calculates AOV', function () {
    $date = '2026-03-15';

    Order::withoutGlobalScopes()->insert([
        [
            'store_id' => $this->store->id,
            'order_number' => '#1001',
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'currency' => 'EUR',
            'subtotal_amount' => 5000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 5000,
            'email' => 'a@test.com',
            'placed_at' => $date.' 10:00:00',
            'created_at' => $date.' 10:00:00',
            'updated_at' => $date.' 10:00:00',
        ],
        [
            'store_id' => $this->store->id,
            'order_number' => '#1002',
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'currency' => 'EUR',
            'subtotal_amount' => 3000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 3000,
            'email' => 'b@test.com',
            'placed_at' => $date.' 14:00:00',
            'created_at' => $date.' 14:00:00',
            'updated_at' => $date.' 14:00:00',
        ],
    ]);

    (new AggregateAnalytics($date))->handle();

    $daily = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('date', $date)
        ->first();

    expect($daily->orders_count)->toBe(2)
        ->and($daily->revenue_amount)->toBe(8000)
        ->and($daily->aov_amount)->toBe(4000);
});

it('does not mix data between stores', function () {
    $date = '2026-03-15';
    $otherContext = createStoreContext('other-store.test');

    AnalyticsEvent::withoutGlobalScopes()->insert([
        ['store_id' => $this->store->id, 'type' => 'page_view', 'session_id' => 'sess-a', 'properties_json' => '{}', 'created_at' => $date.' 10:00:00'],
        ['store_id' => $otherContext['store']->id, 'type' => 'page_view', 'session_id' => 'sess-b', 'properties_json' => '{}', 'created_at' => $date.' 10:00:00'],
        ['store_id' => $otherContext['store']->id, 'type' => 'page_view', 'session_id' => 'sess-c', 'properties_json' => '{}', 'created_at' => $date.' 11:00:00'],
    ]);

    (new AggregateAnalytics($date))->handle();

    $store1Daily = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('date', $date)
        ->first();

    $store2Daily = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $otherContext['store']->id)
        ->where('date', $date)
        ->first();

    expect($store1Daily->visits_count)->toBe(1)
        ->and($store2Daily->visits_count)->toBe(2);
});

it('re-running aggregation updates existing daily row', function () {
    $date = '2026-03-15';

    AnalyticsEvent::withoutGlobalScopes()->insert([
        ['store_id' => $this->store->id, 'type' => 'page_view', 'session_id' => 'sess-1', 'properties_json' => '{}', 'created_at' => $date.' 10:00:00'],
    ]);

    (new AggregateAnalytics($date))->handle();

    $daily = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('date', $date)
        ->first();
    expect($daily->visits_count)->toBe(1);

    // Add more events and re-aggregate
    AnalyticsEvent::withoutGlobalScopes()->insert([
        ['store_id' => $this->store->id, 'type' => 'page_view', 'session_id' => 'sess-2', 'properties_json' => '{}', 'created_at' => $date.' 14:00:00'],
    ]);

    (new AggregateAnalytics($date))->handle();

    $daily = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('date', $date)
        ->first();
    expect($daily->visits_count)->toBe(2);

    // Should still be one row
    $count = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('date', $date)
        ->count();
    expect($count)->toBe(1);
});

it('getDailyMetrics returns data for date range', function () {
    AnalyticsDaily::withoutGlobalScopes()->insert([
        ['store_id' => $this->store->id, 'date' => '2026-03-10', 'orders_count' => 5, 'revenue_amount' => 50000, 'aov_amount' => 10000, 'visits_count' => 100, 'add_to_cart_count' => 20, 'checkout_started_count' => 10, 'checkout_completed_count' => 5],
        ['store_id' => $this->store->id, 'date' => '2026-03-11', 'orders_count' => 3, 'revenue_amount' => 30000, 'aov_amount' => 10000, 'visits_count' => 80, 'add_to_cart_count' => 15, 'checkout_started_count' => 8, 'checkout_completed_count' => 3],
        ['store_id' => $this->store->id, 'date' => '2026-03-12', 'orders_count' => 7, 'revenue_amount' => 70000, 'aov_amount' => 10000, 'visits_count' => 120, 'add_to_cart_count' => 30, 'checkout_started_count' => 15, 'checkout_completed_count' => 7],
    ]);

    $metrics = $this->service->getDailyMetrics($this->store, '2026-03-10', '2026-03-11');

    expect($metrics)->toHaveCount(2)
        ->and($metrics->first()->date)->toBe('2026-03-10')
        ->and($metrics->last()->date)->toBe('2026-03-11');
});

it('produces zero counts for a day with no events or orders', function () {
    $date = '2026-03-15';

    (new AggregateAnalytics($date))->handle();

    $daily = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('date', $date)
        ->first();

    expect($daily)->not->toBeNull()
        ->and($daily->orders_count)->toBe(0)
        ->and($daily->revenue_amount)->toBe(0)
        ->and($daily->aov_amount)->toBe(0)
        ->and($daily->visits_count)->toBe(0)
        ->and($daily->add_to_cart_count)->toBe(0)
        ->and($daily->checkout_started_count)->toBe(0)
        ->and($daily->checkout_completed_count)->toBe(0);
});
