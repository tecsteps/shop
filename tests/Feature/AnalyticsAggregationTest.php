<?php

use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Services\AnalyticsService;
use Illuminate\Support\Facades\DB;

it('aggregates daily orders, integer revenue, sessions, and funnel metrics per tenant', function () {
    $firstStore = shopStore();
    $secondStore = shopStore('second.test');
    $date = '2026-04-10';
    $events = [
        ['type' => 'page_view', 'session_id' => 'session-a'],
        ['type' => 'page_view', 'session_id' => 'session-a'],
        ['type' => 'page_view', 'session_id' => 'session-b'],
        ['type' => 'add_to_cart', 'session_id' => 'session-a'],
        ['type' => 'add_to_cart', 'session_id' => 'session-b'],
        ['type' => 'add_to_cart', 'session_id' => 'session-c'],
        ['type' => 'checkout_started', 'session_id' => 'session-a'],
        ['type' => 'checkout_completed', 'session_id' => 'session-a', 'properties_json' => ['order_total_amount' => 1299]],
        ['type' => 'checkout_completed', 'session_id' => 'session-b', 'properties_json' => ['total_amount' => 2500]],
    ];

    foreach ($events as $index => $event) {
        AnalyticsEvent::create([
            'store_id' => $firstStore->id,
            'client_event_id' => 'first-'.$index,
            'occurred_at' => $date.' 12:00:00',
            'created_at' => $date.' 12:00:00',
            ...$event,
        ]);
    }
    AnalyticsEvent::create([
        'store_id' => $secondStore->id,
        'client_event_id' => 'second-event',
        'type' => 'page_view',
        'session_id' => 'other-session',
        'occurred_at' => $date.' 12:00:00',
        'created_at' => $date.' 12:00:00',
    ]);

    expect(app(AnalyticsService::class)->aggregateForDate($date))->toBe(2);

    $firstDaily = AnalyticsDaily::query()->where('store_id', $firstStore->id)->firstOrFail();
    $secondDaily = AnalyticsDaily::query()->where('store_id', $secondStore->id)->firstOrFail();
    expect($firstDaily->orders_count)->toBe(2)
        ->and($firstDaily->revenue_amount)->toBe(3799)
        ->and($firstDaily->aov_amount)->toBe(1899)
        ->and($firstDaily->visits_count)->toBe(2)
        ->and($firstDaily->add_to_cart_count)->toBe(3)
        ->and($firstDaily->checkout_started_count)->toBe(1)
        ->and($firstDaily->checkout_completed_count)->toBe(2)
        ->and($secondDaily->orders_count)->toBe(0)
        ->and($secondDaily->visits_count)->toBe(1);
});

it('updates an existing day aggregate when it is recalculated', function () {
    $store = shopStore();
    DB::table('analytics_daily')->insert([
        'store_id' => $store->id,
        'date' => '2026-04-11',
        'orders_count' => 19,
        'revenue_amount' => 19000,
        'aov_amount' => 1000,
    ]);

    app(AnalyticsService::class)->aggregateForDate('2026-04-11');

    $daily = DB::table('analytics_daily')->where('store_id', $store->id)->where('date', '2026-04-11')->first();
    expect(DB::table('analytics_daily')->where('store_id', $store->id)->where('date', '2026-04-11')->count())->toBe(1)
        ->and($daily->orders_count)->toBe(0)
        ->and($daily->revenue_amount)->toBe(0);
});
