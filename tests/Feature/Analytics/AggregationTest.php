<?php

use App\Jobs\AggregateAnalytics;
use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Store;
use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('daily aggregation calculates revenue funnel and unique visits', function () {
    $store = Store::factory()->create();
    $date = '2026-07-10';
    AnalyticsEvent::factory()->for($store)->pageView()->create(['session_id' => 'one', 'created_at' => "{$date} 10:00:00"]);
    AnalyticsEvent::factory()->for($store)->pageView()->create(['session_id' => 'one', 'created_at' => "{$date} 10:01:00"]);
    AnalyticsEvent::factory()->for($store)->pageView()->create(['session_id' => 'two', 'created_at' => "{$date} 10:02:00"]);
    AnalyticsEvent::factory()->for($store)->addToCart()->create(['created_at' => "{$date} 10:03:00"]);
    AnalyticsEvent::factory()->for($store)->create(['type' => 'checkout_started', 'created_at' => "{$date} 10:04:00"]);
    AnalyticsEvent::factory()->for($store)->count(2)->create([
        'type' => 'checkout_completed',
        'properties_json' => ['total_amount' => 2500],
        'created_at' => "{$date} 10:05:00",
    ]);

    (new AggregateAnalytics($date))->handle();

    $daily = AnalyticsDaily::withoutGlobalScopes()->sole();

    expect($daily->orders_count)->toBe(2)
        ->and($daily->revenue_amount)->toBe(5000)
        ->and($daily->aov_amount)->toBe(2500)
        ->and($daily->visits_count)->toBe(2)
        ->and($daily->add_to_cart_count)->toBe(1)
        ->and($daily->checkout_started_count)->toBe(1)
        ->and($daily->checkout_completed_count)->toBe(2)
        ->and(app(AnalyticsService::class)->getDailyMetrics($store, $date, $date))->toHaveCount(1);
});
