<?php

use App\Jobs\AggregateAnalytics;
use App\Models\AnalyticsEvent;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Store;
use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
});

it('tracks an analytics event', function () {
    $service = app(AnalyticsService::class);

    $service->track($this->store, 'page_view', ['url' => '/'], 'session-123');

    $this->assertDatabaseHas('analytics_events', [
        'store_id' => $this->store->id,
        'type' => 'page_view',
        'session_id' => 'session-123',
    ]);
});

it('tracks events with customer id', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);

    $service = app(AnalyticsService::class);

    $service->track($this->store, 'product_view', ['product_id' => 42], 'session-456', $customer->id);

    $event = AnalyticsEvent::query()->withoutGlobalScopes()->first();

    expect($event->type)->toBe('product_view')
        ->and($event->customer_id)->toBe($customer->id)
        ->and($event->properties_json)->toBe(['product_id' => 42]);
});

it('aggregates daily metrics', function () {
    $yesterday = now()->subDay()->format('Y-m-d');

    AnalyticsEvent::query()->create([
        'store_id' => $this->store->id,
        'type' => 'page_view',
        'session_id' => 'session-a',
        'created_at' => $yesterday.' 10:00:00',
    ]);

    AnalyticsEvent::query()->create([
        'store_id' => $this->store->id,
        'type' => 'page_view',
        'session_id' => 'session-b',
        'created_at' => $yesterday.' 11:00:00',
    ]);

    AnalyticsEvent::query()->create([
        'store_id' => $this->store->id,
        'type' => 'add_to_cart',
        'session_id' => 'session-a',
        'created_at' => $yesterday.' 10:05:00',
    ]);

    $job = new AggregateAnalytics($yesterday);
    $job->handle();

    $daily = DB::table('analytics_daily')
        ->where('store_id', $this->store->id)
        ->where('date', $yesterday)
        ->first();

    expect($daily)->not->toBeNull()
        ->and($daily->visits_count)->toBe(2)
        ->and($daily->add_to_cart_count)->toBe(1);
});

it('calculates order metrics in aggregation', function () {
    $yesterday = now()->subDay()->format('Y-m-d');

    Order::factory()->create([
        'store_id' => $this->store->id,
        'total_amount' => 5000,
        'placed_at' => $yesterday.' 12:00:00',
    ]);

    Order::factory()->create([
        'store_id' => $this->store->id,
        'total_amount' => 3000,
        'placed_at' => $yesterday.' 14:00:00',
    ]);

    $job = new AggregateAnalytics($yesterday);
    $job->handle();

    $daily = DB::table('analytics_daily')
        ->where('store_id', $this->store->id)
        ->where('date', $yesterday)
        ->first();

    expect($daily->orders_count)->toBe(2)
        ->and($daily->revenue_amount)->toBe(8000)
        ->and($daily->aov_amount)->toBe(4000);
});

it('retrieves daily metrics for a date range', function () {
    DB::table('analytics_daily')->insert([
        'store_id' => $this->store->id,
        'date' => '2026-03-01',
        'orders_count' => 5,
        'revenue_amount' => 25000,
        'aov_amount' => 5000,
        'visits_count' => 100,
        'add_to_cart_count' => 20,
        'checkout_started_count' => 10,
        'checkout_completed_count' => 5,
    ]);

    $service = app(AnalyticsService::class);
    $metrics = $service->getDailyMetrics($this->store, '2026-03-01', '2026-03-31');

    expect($metrics)->toHaveCount(1)
        ->and($metrics->first()->orders_count)->toBe(5);
});

it('creates analytics event model with factory', function () {
    $event = AnalyticsEvent::factory()->pageView()->create([
        'store_id' => $this->store->id,
    ]);

    expect($event->type)->toBe('page_view')
        ->and($event->properties_json)->toBe(['url' => '/']);
});
