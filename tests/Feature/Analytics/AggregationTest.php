<?php

use App\Jobs\AggregateAnalytics;
use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->date = now()->subDay()->toDateString();
});

it('aggregates daily metrics from raw events', function () {
    AnalyticsEvent::factory()->count(5)->pageView()->for($this->store)->create([
        'created_at' => now()->subDay()->setTime(10, 0),
    ]);
    AnalyticsEvent::factory()->count(3)->addToCart()->for($this->store)->create([
        'created_at' => now()->subDay()->setTime(11, 0),
    ]);
    AnalyticsEvent::factory()->count(2)->for($this->store)->create([
        'type' => 'checkout_completed',
        'properties_json' => ['order_id' => 1, 'total_amount' => 5000],
        'created_at' => now()->subDay()->setTime(12, 0),
    ]);

    (new AggregateAnalytics($this->date))->handle();

    $daily = AnalyticsDaily::query()
        ->where('store_id', $this->store->getKey())
        ->where('date', $this->date)
        ->firstOrFail();

    expect($daily->visits_count)->toBe(5);
    expect($daily->add_to_cart_count)->toBe(3);
    expect($daily->checkout_completed_count)->toBe(2);
    expect($daily->orders_count)->toBe(2);
});

it('calculates revenue and AOV correctly', function () {
    foreach ([1000, 2000, 3000] as $totalAmount) {
        AnalyticsEvent::factory()->for($this->store)->create([
            'type' => 'checkout_completed',
            'properties_json' => ['total_amount' => $totalAmount],
            'created_at' => now()->subDay()->setTime(12, 0),
        ]);
    }

    (new AggregateAnalytics($this->date))->handle();

    $daily = AnalyticsDaily::query()
        ->where('store_id', $this->store->getKey())
        ->where('date', $this->date)
        ->firstOrFail();

    expect($daily->revenue_amount)->toBe(6000);
    expect($daily->aov_amount)->toBe(2000);
    expect($daily->orders_count)->toBe(3);
});

it('runs idempotently', function () {
    AnalyticsEvent::factory()->count(4)->pageView()->for($this->store)->create([
        'created_at' => now()->subDay()->setTime(9, 0),
    ]);
    AnalyticsEvent::factory()->for($this->store)->create([
        'type' => 'checkout_completed',
        'properties_json' => ['total_amount' => 2500],
        'created_at' => now()->subDay()->setTime(10, 0),
    ]);

    (new AggregateAnalytics($this->date))->handle();
    (new AggregateAnalytics($this->date))->handle();

    $rows = AnalyticsDaily::query()
        ->where('store_id', $this->store->getKey())
        ->where('date', $this->date)
        ->get();

    expect($rows)->toHaveCount(1);
    expect($rows->first()->visits_count)->toBe(4);
    expect($rows->first()->revenue_amount)->toBe(2500);
    expect($rows->first()->orders_count)->toBe(1);
});
