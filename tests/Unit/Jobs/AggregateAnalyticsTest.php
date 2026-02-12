<?php

use App\Jobs\AggregateAnalytics;
use App\Models\AnalyticsEvent;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

it('aggregates analytics events into daily metrics', function () {
    $storeA = Store::factory()->create();
    $storeB = Store::factory()->create();
    $date = '2026-02-10';

    AnalyticsEvent::query()->create([
        'store_id' => $storeA->id,
        'type' => 'checkout_completed',
        'session_id' => 's-checkout-1',
        'properties_json' => ['order_total_amount' => 1000],
        'occurred_at' => "{$date} 12:00:00",
        'created_at' => "{$date} 12:00:00",
    ]);

    AnalyticsEvent::query()->create([
        'store_id' => $storeA->id,
        'type' => 'checkout_completed',
        'session_id' => 's-checkout-2',
        'properties_json' => ['revenue_amount' => 500],
        'occurred_at' => "{$date} 12:30:00",
        'created_at' => "{$date} 12:30:00",
    ]);

    AnalyticsEvent::query()->create([
        'store_id' => $storeA->id,
        'type' => 'page_view',
        'session_id' => 'visit-1',
        'properties_json' => [],
        'occurred_at' => "{$date} 09:00:00",
        'created_at' => "{$date} 09:00:00",
    ]);

    AnalyticsEvent::query()->create([
        'store_id' => $storeA->id,
        'type' => 'page_view',
        'session_id' => 'visit-1',
        'properties_json' => [],
        'occurred_at' => "{$date} 09:05:00",
        'created_at' => "{$date} 09:05:00",
    ]);

    AnalyticsEvent::query()->create([
        'store_id' => $storeA->id,
        'type' => 'page_view',
        'session_id' => 'visit-2',
        'properties_json' => [],
        'occurred_at' => "{$date} 09:10:00",
        'created_at' => "{$date} 09:10:00",
    ]);

    AnalyticsEvent::query()->create([
        'store_id' => $storeA->id,
        'type' => 'add_to_cart',
        'session_id' => 'visit-1',
        'properties_json' => [],
        'occurred_at' => "{$date} 10:00:00",
        'created_at' => "{$date} 10:00:00",
    ]);

    AnalyticsEvent::query()->create([
        'store_id' => $storeA->id,
        'type' => 'add_to_cart',
        'session_id' => 'visit-2',
        'properties_json' => [],
        'occurred_at' => "{$date} 10:10:00",
        'created_at' => "{$date} 10:10:00",
    ]);

    AnalyticsEvent::query()->create([
        'store_id' => $storeA->id,
        'type' => 'checkout_started',
        'session_id' => 'visit-2',
        'properties_json' => [],
        'occurred_at' => "{$date} 10:15:00",
        'created_at' => "{$date} 10:15:00",
    ]);

    AnalyticsEvent::query()->create([
        'store_id' => $storeA->id,
        'type' => 'checkout_completed',
        'session_id' => 'ignored',
        'properties_json' => ['order_total_amount' => 9999],
        'occurred_at' => '2026-02-11 10:00:00',
        'created_at' => '2026-02-11 10:00:00',
    ]);

    AnalyticsEvent::query()->create([
        'store_id' => $storeB->id,
        'type' => 'checkout_completed',
        'session_id' => 'b-1',
        'properties_json' => ['total_amount' => 900],
        'occurred_at' => "{$date} 08:00:00",
        'created_at' => "{$date} 08:00:00",
    ]);

    (new AggregateAnalytics($date))->handle();

    $rowA = DB::table('analytics_daily')
        ->where('store_id', $storeA->id)
        ->where('date', $date)
        ->first();

    $rowB = DB::table('analytics_daily')
        ->where('store_id', $storeB->id)
        ->where('date', $date)
        ->first();

    expect($rowA)->not->toBeNull()
        ->and((int) $rowA->orders_count)->toBe(2)
        ->and((int) $rowA->revenue_amount)->toBe(1500)
        ->and((int) $rowA->aov_amount)->toBe(750)
        ->and((int) $rowA->visits_count)->toBe(2)
        ->and((int) $rowA->add_to_cart_count)->toBe(2)
        ->and((int) $rowA->checkout_started_count)->toBe(1)
        ->and((int) $rowA->checkout_completed_count)->toBe(2);

    expect($rowB)->not->toBeNull()
        ->and((int) $rowB->orders_count)->toBe(1)
        ->and((int) $rowB->revenue_amount)->toBe(900)
        ->and((int) $rowB->aov_amount)->toBe(900);
});
