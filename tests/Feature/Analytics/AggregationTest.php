<?php

use App\Jobs\AggregateAnalytics;
use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;

it('aggregates daily metrics from raw events', function () {
    $ctx = createStoreContext();
    $date = now()->subDay()->toDateString();

    foreach (range(1, 5) as $i) {
        AnalyticsEvent::factory()->create(['store_id' => $ctx['store']->id, 'type' => 'page_view', 'session_id' => 's'.$i, 'created_at' => now()->subDay()]);
    }
    foreach (range(1, 3) as $i) {
        AnalyticsEvent::factory()->create(['store_id' => $ctx['store']->id, 'type' => 'add_to_cart', 'created_at' => now()->subDay()]);
    }
    foreach (range(1, 2) as $i) {
        AnalyticsEvent::factory()->create(['store_id' => $ctx['store']->id, 'type' => 'checkout_completed', 'properties_json' => ['total' => 2000], 'created_at' => now()->subDay()]);
    }

    (new AggregateAnalytics)->handle();

    $daily = AnalyticsDaily::where('store_id', $ctx['store']->id)->where('date', $date)->first();
    expect($daily)->not->toBeNull();
    expect($daily->orders_count)->toBe(2);
    expect($daily->add_to_cart_count)->toBe(3);
    expect($daily->visits_count)->toBe(5);
    expect($daily->revenue_amount)->toBe(4000);
});
