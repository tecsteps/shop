<?php

use App\Jobs\AggregateAnalytics;
use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Order;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->yesterday = now()->subDay()->toDateString();
    $this->yesterdayTime = now()->subDay()->startOfDay()->addHours(10);
});

function createEvent(int $storeId, string $type, string $sessionId, $createdAt): AnalyticsEvent
{
    $event = new AnalyticsEvent;
    $event->store_id = $storeId;
    $event->event_type = $type;
    $event->session_id = $sessionId;
    $event->properties_json = [];
    $event->created_at = $createdAt;
    $event->saveQuietly();

    return $event;
}

it('aggregates daily metrics from raw events', function () {
    createEvent($this->store->id, 'page_view', 'sess-1', $this->yesterdayTime);
    createEvent($this->store->id, 'page_view', 'sess-2', $this->yesterdayTime);
    createEvent($this->store->id, 'add_to_cart', 'sess-1', $this->yesterdayTime);
    createEvent($this->store->id, 'checkout_started', 'sess-1', $this->yesterdayTime);

    $job = new AggregateAnalytics($this->yesterday);
    $job->handle();

    $daily = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('date', $this->yesterday)
        ->first();

    expect($daily)->not->toBeNull()
        ->and($daily->visits_count)->toBe(2)
        ->and($daily->add_to_cart_count)->toBe(1)
        ->and($daily->checkout_started_count)->toBe(1);
});

it('calculates revenue and AOV correctly', function () {
    createEvent($this->store->id, 'checkout_completed', 'sess-1', $this->yesterdayTime);
    createEvent($this->store->id, 'checkout_completed', 'sess-2', $this->yesterdayTime);

    Order::factory()->create([
        'store_id' => $this->store->id,
        'order_number' => 'ORD-001',
        'total_amount' => 5000,
        'placed_at' => $this->yesterdayTime,
    ]);

    Order::factory()->create([
        'store_id' => $this->store->id,
        'order_number' => 'ORD-002',
        'total_amount' => 3000,
        'placed_at' => $this->yesterdayTime,
    ]);

    $job = new AggregateAnalytics($this->yesterday);
    $job->handle();

    $daily = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('date', $this->yesterday)
        ->first();

    expect($daily)->not->toBeNull()
        ->and($daily->orders_count)->toBe(2)
        ->and($daily->revenue_amount)->toBe(8000)
        ->and($daily->aov_amount)->toBe(4000);
});

it('runs idempotently', function () {
    createEvent($this->store->id, 'page_view', 'sess-1', $this->yesterdayTime);

    $job = new AggregateAnalytics($this->yesterday);
    $job->handle();
    $job->handle();

    $count = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('date', $this->yesterday)
        ->count();

    expect($count)->toBe(1);
});
