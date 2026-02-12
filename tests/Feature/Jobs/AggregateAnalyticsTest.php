<?php

use App\Jobs\AggregateAnalytics;
use App\Models\AnalyticsDaily;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
});

/**
 * Insert a raw analytics event using DB facade to control created_at.
 */
function insertEvent(int $storeId, string $eventType, array $properties, string $sessionId, string $createdAt): void
{
    DB::table('analytics_events')->insert([
        'store_id' => $storeId,
        'event_type' => $eventType,
        'properties_json' => json_encode($properties),
        'session_id' => $sessionId,
        'created_at' => $createdAt,
    ]);
}

test('job aggregates events into daily metrics', function () {
    $date = '2025-06-15';

    // Page views with unique sessions
    for ($i = 0; $i < 10; $i++) {
        insertEvent($this->store->id, 'page_view', [], 'session-'.($i % 5), $date.' 10:00:00');
    }

    // Add to cart events
    for ($i = 0; $i < 3; $i++) {
        insertEvent($this->store->id, 'add_to_cart', [], 'session-'.$i, $date.' 11:00:00');
    }

    // Checkout started
    insertEvent($this->store->id, 'checkout_started', [], 'session-0', $date.' 12:00:00');

    // Checkout completed with revenue
    insertEvent($this->store->id, 'checkout_completed', ['total_amount' => 5000], 'session-0', $date.' 12:30:00');
    insertEvent($this->store->id, 'checkout_completed', ['total_amount' => 3000], 'session-1', $date.' 13:00:00');

    // Product views
    for ($i = 0; $i < 4; $i++) {
        insertEvent($this->store->id, 'product_view', ['product_title' => 'Widget A'], 'session-'.$i, $date.' 14:00:00');
    }
    insertEvent($this->store->id, 'product_view', ['product_title' => 'Widget B'], 'session-0', $date.' 14:30:00');

    (new AggregateAnalytics($date))->handle();

    $getMetric = function (string $metric, ?string $dimension = null) use ($date) {
        $query = AnalyticsDaily::withoutGlobalScopes()
            ->where('store_id', $this->store->id)
            ->whereDate('date', $date)
            ->where('metric', $metric);

        if ($dimension === null) {
            $query->whereNull('dimension');
        } else {
            $query->where('dimension', $dimension);
        }

        return $query->first();
    };

    expect($getMetric('visits_count')->value)->toBe(5)
        ->and($getMetric('page_views')->value)->toBe(10)
        ->and($getMetric('add_to_cart_count')->value)->toBe(3)
        ->and($getMetric('checkout_started_count')->value)->toBe(1)
        ->and($getMetric('orders_count')->value)->toBe(2)
        ->and($getMetric('revenue_amount')->value)->toBe(8000)
        ->and($getMetric('aov_amount')->value)->toBe(4000)
        ->and($getMetric('product_views', 'Widget A')->value)->toBe(4)
        ->and($getMetric('product_views', 'Widget B')->value)->toBe(1);
});

test('job handles multiple stores independently', function () {
    $otherStore = Store::factory()->create();
    $date = '2025-06-15';

    // Events for store 1
    insertEvent($this->store->id, 'page_view', [], 'session-a', $date.' 10:00:00');
    insertEvent($this->store->id, 'checkout_completed', ['total_amount' => 2000], 'session-a', $date.' 11:00:00');

    // Events for store 2
    insertEvent($otherStore->id, 'page_view', [], 'session-b', $date.' 10:00:00');
    insertEvent($otherStore->id, 'page_view', [], 'session-c', $date.' 10:30:00');

    (new AggregateAnalytics($date))->handle();

    $store1PageViews = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('metric', 'page_views')
        ->first();

    $store2PageViews = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $otherStore->id)
        ->where('metric', 'page_views')
        ->first();

    $store1Revenue = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('metric', 'revenue_amount')
        ->first();

    $store2Revenue = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $otherStore->id)
        ->where('metric', 'revenue_amount')
        ->first();

    expect($store1PageViews->value)->toBe(1)
        ->and($store2PageViews->value)->toBe(2)
        ->and($store1Revenue->value)->toBe(2000)
        ->and($store2Revenue->value)->toBe(0);
});

test('job creates correct metrics with updateOrCreate', function () {
    $date = '2025-06-15';

    // Pre-existing metric that should be updated
    AnalyticsDaily::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'date' => $date,
        'metric' => 'page_views',
        'value' => 999,
        'dimension' => null,
    ]);

    insertEvent($this->store->id, 'page_view', [], 'session-1', $date.' 10:00:00');

    (new AggregateAnalytics($date))->handle();

    $pageViews = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('metric', 'page_views')
        ->whereNull('dimension')
        ->get();

    // Should have exactly one record (updated, not duplicated)
    expect($pageViews)->toHaveCount(1)
        ->and($pageViews->first()->value)->toBe(1);
});

test('job defaults to yesterday when no date given', function () {
    $yesterday = now()->subDay()->toDateString();

    insertEvent($this->store->id, 'page_view', [], 'session-1', $yesterday.' 10:00:00');

    (new AggregateAnalytics)->handle();

    $metric = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->whereDate('date', $yesterday)
        ->where('metric', 'page_views')
        ->first();

    expect($metric)->not->toBeNull()
        ->and($metric->value)->toBe(1);
});

test('job calculates aov as zero when no orders exist', function () {
    $date = '2025-06-15';

    insertEvent($this->store->id, 'page_view', [], 'session-1', $date.' 10:00:00');

    (new AggregateAnalytics($date))->handle();

    $aov = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('metric', 'aov_amount')
        ->first();

    expect($aov->value)->toBe(0);
});
