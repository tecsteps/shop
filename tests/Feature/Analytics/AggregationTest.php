<?php

use App\Jobs\AggregateAnalytics;
use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Services\AnalyticsService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->yesterday = now()->subDay()->format('Y-m-d');
    $this->yesterdayTimestamp = $this->yesterday.'T12:00:00';
});

it('aggregates visits from unique sessions', function () {
    AnalyticsEvent::factory()->pageView()->count(3)->create([
        'store_id' => $this->store->id,
        'session_id' => 'sess-a',
        'created_at' => $this->yesterdayTimestamp,
    ]);
    AnalyticsEvent::factory()->pageView()->count(2)->create([
        'store_id' => $this->store->id,
        'session_id' => 'sess-b',
        'created_at' => $this->yesterdayTimestamp,
    ]);

    (new AggregateAnalytics($this->yesterday))->handle();

    $daily = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('date', $this->yesterday)
        ->first();

    expect($daily)->not->toBeNull()
        ->and($daily->visits_count)->toBe(2);
});

it('aggregates add_to_cart events', function () {
    AnalyticsEvent::factory()->addToCart()->count(4)->create([
        'store_id' => $this->store->id,
        'created_at' => $this->yesterdayTimestamp,
    ]);

    (new AggregateAnalytics($this->yesterday))->handle();

    $daily = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('date', $this->yesterday)
        ->first();

    expect($daily->add_to_cart_count)->toBe(4);
});

it('aggregates checkout_started events', function () {
    AnalyticsEvent::factory()->checkoutStarted()->count(2)->create([
        'store_id' => $this->store->id,
        'created_at' => $this->yesterdayTimestamp,
    ]);

    (new AggregateAnalytics($this->yesterday))->handle();

    $daily = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('date', $this->yesterday)
        ->first();

    expect($daily->checkout_started_count)->toBe(2);
});

it('aggregates orders revenue and aov', function () {
    AnalyticsEvent::factory()->checkoutCompleted(5000)->create([
        'store_id' => $this->store->id,
        'created_at' => $this->yesterdayTimestamp,
    ]);
    AnalyticsEvent::factory()->checkoutCompleted(3000)->create([
        'store_id' => $this->store->id,
        'created_at' => $this->yesterdayTimestamp,
    ]);

    (new AggregateAnalytics($this->yesterday))->handle();

    $daily = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('date', $this->yesterday)
        ->first();

    expect($daily->orders_count)->toBe(2)
        ->and($daily->revenue_amount)->toBe(8000)
        ->and($daily->aov_amount)->toBe(4000);
});

it('handles zero orders gracefully', function () {
    AnalyticsEvent::factory()->pageView()->count(3)->create([
        'store_id' => $this->store->id,
        'created_at' => $this->yesterdayTimestamp,
    ]);

    (new AggregateAnalytics($this->yesterday))->handle();

    $daily = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('date', $this->yesterday)
        ->first();

    expect($daily->orders_count)->toBe(0)
        ->and($daily->revenue_amount)->toBe(0)
        ->and($daily->aov_amount)->toBe(0);
});

it('retrieves daily metrics for a date range', function () {
    $service = new AnalyticsService;

    for ($i = 1; $i <= 7; $i++) {
        AnalyticsDaily::withoutGlobalScopes()->create([
            'store_id' => $this->store->id,
            'date' => "2026-03-0{$i}",
            'orders_count' => $i,
            'revenue_amount' => $i * 1000,
            'aov_amount' => 1000,
            'visits_count' => $i * 10,
            'add_to_cart_count' => $i * 2,
            'checkout_started_count' => $i,
            'checkout_completed_count' => $i,
        ]);
    }

    $metrics = $service->getDailyMetrics($this->store, '2026-03-01', '2026-03-07');

    expect($metrics)->toHaveCount(7)
        ->and($metrics->first()->date)->toBe('2026-03-01')
        ->and($metrics->last()->date)->toBe('2026-03-07');
});
