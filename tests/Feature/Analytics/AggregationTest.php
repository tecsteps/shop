<?php

use App\Jobs\AggregateAnalytics;
use App\Services\AnalyticsService;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->store = $this->createStore();
    $this->bindStore($this->store);
    $this->analytics = app(AnalyticsService::class);
});

/**
 * Insert a raw event at a fixed point in time.
 *
 * @param  array<string, mixed>  $properties
 */
function aggregateEvent(App\Models\Store $store, string $type, array $properties = [], ?string $sessionId = null, string $occurredAt = '2026-01-10 12:00:00'): void
{
    app(AnalyticsService::class)->track($store, $type, $properties, $sessionId, null, null, $occurredAt);
}

/**
 * Read the analytics_daily row for the store and date.
 */
function aggregateRow(int $storeId, string $date): ?object
{
    return DB::table('analytics_daily')
        ->where('store_id', $storeId)
        ->where('date', $date)
        ->first();
}

test('aggregates daily metrics from raw events', function () {
    // 5 page views across 3 distinct sessions.
    aggregateEvent($this->store, 'page_view', [], 'sess_1');
    aggregateEvent($this->store, 'page_view', [], 'sess_1');
    aggregateEvent($this->store, 'page_view', [], 'sess_2');
    aggregateEvent($this->store, 'page_view', [], 'sess_3');
    aggregateEvent($this->store, 'page_view', [], 'sess_3');

    // 3 add to carts, 2 checkouts started, 2 completed (1000 + 2000 cents).
    aggregateEvent($this->store, 'add_to_cart', ['product_id' => 1], 'sess_1');
    aggregateEvent($this->store, 'add_to_cart', ['product_id' => 2], 'sess_2');
    aggregateEvent($this->store, 'add_to_cart', ['product_id' => 1], 'sess_3');
    aggregateEvent($this->store, 'checkout_started', [], 'sess_1');
    aggregateEvent($this->store, 'checkout_started', [], 'sess_2');
    aggregateEvent($this->store, 'checkout_completed', ['total' => 1000], 'sess_1');
    aggregateEvent($this->store, 'checkout_completed', ['total' => 2000], 'sess_2');

    // A different day must not leak into the aggregate.
    aggregateEvent($this->store, 'page_view', [], 'sess_9', '2026-01-11 08:00:00');

    (new AggregateAnalytics('2026-01-10'))->handle($this->analytics);

    $row = aggregateRow($this->store->id, '2026-01-10');

    expect($row)->not->toBeNull()
        ->and((int) $row->orders_count)->toBe(2)
        ->and((int) $row->revenue_amount)->toBe(3000)
        ->and((int) $row->aov_amount)->toBe(1500)
        ->and((int) $row->visits_count)->toBe(3)
        ->and((int) $row->add_to_cart_count)->toBe(3)
        ->and((int) $row->checkout_started_count)->toBe(2)
        ->and((int) $row->checkout_completed_count)->toBe(2);
});

test('calculates revenue and aov correctly', function () {
    aggregateEvent($this->store, 'checkout_completed', ['total' => 1000]);
    aggregateEvent($this->store, 'checkout_completed', ['total' => 2000]);
    aggregateEvent($this->store, 'checkout_completed', ['total' => 3000]);

    (new AggregateAnalytics('2026-01-10'))->handle($this->analytics);

    $row = aggregateRow($this->store->id, '2026-01-10');

    expect((int) $row->orders_count)->toBe(3)
        ->and((int) $row->revenue_amount)->toBe(6000)
        ->and((int) $row->aov_amount)->toBe(2000);
});

test('calculates aov with integer division', function () {
    aggregateEvent($this->store, 'checkout_completed', ['total' => 1001]);
    aggregateEvent($this->store, 'checkout_completed', ['total' => 1000]);

    (new AggregateAnalytics('2026-01-10'))->handle($this->analytics);

    $row = aggregateRow($this->store->id, '2026-01-10');

    // intdiv(2001, 2) truncates toward zero.
    expect((int) $row->revenue_amount)->toBe(2001)
        ->and((int) $row->aov_amount)->toBe(1000);
});

test('runs idempotently', function () {
    aggregateEvent($this->store, 'page_view', [], 'sess_1');
    aggregateEvent($this->store, 'checkout_completed', ['total' => 5000]);

    (new AggregateAnalytics('2026-01-10'))->handle($this->analytics);
    (new AggregateAnalytics('2026-01-10'))->handle($this->analytics);

    $rows = DB::table('analytics_daily')
        ->where('store_id', $this->store->id)
        ->where('date', '2026-01-10')
        ->get();

    expect($rows)->toHaveCount(1)
        ->and((int) $rows->first()->orders_count)->toBe(1)
        ->and((int) $rows->first()->revenue_amount)->toBe(5000)
        ->and((int) $rows->first()->visits_count)->toBe(1);
});

test('counts distinct sessions with page views as visits', function () {
    aggregateEvent($this->store, 'page_view', [], 'sess_1');
    aggregateEvent($this->store, 'page_view', [], 'sess_1');
    aggregateEvent($this->store, 'page_view', [], 'sess_1');
    aggregateEvent($this->store, 'page_view', [], 'sess_2');
    aggregateEvent($this->store, 'page_view', [], 'sess_2');
    // Page views without a session id are not visits.
    aggregateEvent($this->store, 'page_view', [], null);
    // Non-page-view events do not count as visits.
    aggregateEvent($this->store, 'product_view', [], 'sess_3');

    (new AggregateAnalytics('2026-01-10'))->handle($this->analytics);

    $row = aggregateRow($this->store->id, '2026-01-10');

    expect((int) $row->visits_count)->toBe(2);
});

test('aggregates the previous day by default', function () {
    Carbon\Carbon::setTestNow('2026-03-15 04:00:00');

    aggregateEvent($this->store, 'page_view', [], 'sess_1', '2026-03-14 23:30:00');
    aggregateEvent($this->store, 'checkout_completed', ['total' => 4200], 'sess_1', '2026-03-14 23:45:00');

    (new AggregateAnalytics)->handle($this->analytics);

    $row = aggregateRow($this->store->id, '2026-03-14');

    expect($row)->not->toBeNull()
        ->and((int) $row->visits_count)->toBe(1)
        ->and((int) $row->revenue_amount)->toBe(4200);

    Carbon\Carbon::setTestNow();
});

test('keeps stores isolated during aggregation', function () {
    $otherStore = $this->createStore();

    aggregateEvent($this->store, 'checkout_completed', ['total' => 1000]);
    aggregateEvent($otherStore, 'checkout_completed', ['total' => 9000]);

    (new AggregateAnalytics('2026-01-10'))->handle($this->analytics);

    expect((int) aggregateRow($this->store->id, '2026-01-10')->revenue_amount)->toBe(1000)
        ->and((int) aggregateRow($otherStore->id, '2026-01-10')->revenue_amount)->toBe(9000);
});
