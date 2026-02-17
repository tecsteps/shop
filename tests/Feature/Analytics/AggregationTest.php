<?php

use App\Jobs\AggregateAnalytics;
use App\Models\AnalyticsEvent;
use App\Models\Order;
use App\Models\Organization;
use App\Models\Store;

beforeEach(function () {
    $org = Organization::factory()->create();
    $this->store = Store::factory()->for($org)->create();
    app()->instance('current_store', $this->store);
});

test('aggregation creates daily record from events', function () {
    $date = '2026-02-16';

    AnalyticsEvent::factory()->count(3)->create([
        'store_id' => $this->store->id,
        'type' => 'page_view',
        'session_id' => 'sess-unique',
        'created_at' => $date.' 10:00:00',
    ]);
    AnalyticsEvent::factory()->create([
        'store_id' => $this->store->id,
        'type' => 'page_view',
        'session_id' => 'sess-other',
        'created_at' => $date.' 11:00:00',
    ]);
    AnalyticsEvent::factory()->create([
        'store_id' => $this->store->id,
        'type' => 'add_to_cart',
        'created_at' => $date.' 12:00:00',
    ]);
    AnalyticsEvent::factory()->create([
        'store_id' => $this->store->id,
        'type' => 'checkout_started',
        'created_at' => $date.' 13:00:00',
    ]);
    AnalyticsEvent::factory()->create([
        'store_id' => $this->store->id,
        'type' => 'checkout_completed',
        'created_at' => $date.' 14:00:00',
    ]);

    Order::factory()->create([
        'store_id' => $this->store->id,
        'total_amount' => 5000,
        'placed_at' => $date.' 15:00:00',
    ]);

    (new AggregateAnalytics($date))->handle();

    $this->assertDatabaseHas('analytics_daily', [
        'store_id' => $this->store->id,
        'date' => $date,
        'orders_count' => 1,
        'revenue_amount' => 5000,
        'aov_amount' => 5000,
        'visits_count' => 2,
        'add_to_cart_count' => 1,
        'checkout_started_count' => 1,
        'checkout_completed_count' => 1,
    ]);
});

test('get daily metrics returns data in range', function () {
    \Illuminate\Support\Facades\DB::table('analytics_daily')->insert([
        ['store_id' => $this->store->id, 'date' => '2026-02-14', 'orders_count' => 5, 'revenue_amount' => 10000, 'aov_amount' => 2000, 'visits_count' => 100, 'add_to_cart_count' => 20, 'checkout_started_count' => 10, 'checkout_completed_count' => 5],
        ['store_id' => $this->store->id, 'date' => '2026-02-15', 'orders_count' => 3, 'revenue_amount' => 6000, 'aov_amount' => 2000, 'visits_count' => 80, 'add_to_cart_count' => 15, 'checkout_started_count' => 8, 'checkout_completed_count' => 3],
    ]);

    $service = new \App\Services\AnalyticsService;
    $metrics = $service->getDailyMetrics($this->store, '2026-02-14', '2026-02-15');

    expect($metrics)->toHaveCount(2);
    expect($metrics->first()->orders_count)->toBe(5);
});

test('aggregation handles store with no events gracefully', function () {
    $date = '2026-02-16';

    (new AggregateAnalytics($date))->handle();

    $this->assertDatabaseHas('analytics_daily', [
        'store_id' => $this->store->id,
        'date' => $date,
        'orders_count' => 0,
        'revenue_amount' => 0,
        'visits_count' => 0,
    ]);
});
