<?php

use App\Jobs\AggregateAnalytics;
use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Order;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->date = '2026-05-15';
    $this->at = Carbon::parse($this->date)->setTime(12, 0);
});

/**
 * Insert a raw analytics event dated on the aggregation day. `created_at` is set
 * by freezing the clock, since it is guarded (not mass-assignable).
 */
function eventOn(int $storeId, string $type, Carbon $at, ?string $session = null): void
{
    Carbon::setTestNow($at);

    AnalyticsEvent::create([
        'store_id' => $storeId,
        'type' => $type,
        'session_id' => $session ?? 'sess_'.uniqid(),
        'properties_json' => [],
    ]);

    Carbon::setTestNow();
}

it('aggregates daily metrics from raw events', function () {
    foreach (range(1, 5) as $i) {
        eventOn($this->store->id, 'page_view', $this->at, "sess_{$i}");
    }
    foreach (range(1, 3) as $i) {
        eventOn($this->store->id, 'add_to_cart', $this->at, "sess_{$i}");
    }
    foreach (range(1, 2) as $i) {
        eventOn($this->store->id, 'checkout_completed', $this->at, "sess_{$i}");
    }

    (new AggregateAnalytics($this->date))->handle();

    $daily = AnalyticsDaily::query()->where('store_id', $this->store->id)->where('date', $this->date)->first();

    expect($daily)->not->toBeNull()
        ->and($daily->visits_count)->toBe(5)
        ->and($daily->add_to_cart_count)->toBe(3)
        ->and($daily->checkout_completed_count)->toBe(2);
});

it('calculates revenue and AOV correctly', function () {
    foreach ([1000, 2000, 3000] as $total) {
        Order::factory()->create([
            'store_id' => $this->store->id,
            'total_amount' => $total,
            'placed_at' => $this->at,
        ]);
    }

    (new AggregateAnalytics($this->date))->handle();

    $daily = AnalyticsDaily::query()->where('store_id', $this->store->id)->where('date', $this->date)->first();

    expect($daily->orders_count)->toBe(3)
        ->and($daily->revenue_amount)->toBe(6000)
        ->and($daily->aov_amount)->toBe(2000);
});

it('runs idempotently', function () {
    Order::factory()->create([
        'store_id' => $this->store->id,
        'total_amount' => 5000,
        'placed_at' => $this->at,
    ]);
    eventOn($this->store->id, 'add_to_cart', $this->at, 'sess_1');

    (new AggregateAnalytics($this->date))->handle();
    (new AggregateAnalytics($this->date))->handle();

    $rows = AnalyticsDaily::query()->where('store_id', $this->store->id)->where('date', $this->date)->get();

    expect($rows)->toHaveCount(1)
        ->and($rows->first()->revenue_amount)->toBe(5000)
        ->and($rows->first()->orders_count)->toBe(1)
        ->and($rows->first()->add_to_cart_count)->toBe(1);
});
