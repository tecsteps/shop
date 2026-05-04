<?php

use App\Enums\AnalyticsEventType;
use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Store;
use App\Services\AnalyticsService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function analyticsServiceStore(): Store
{
    return Store::query()->where('handle', 'acme-fashion')->firstOrFail();
}

test('analytics service tracks events and drops duplicate client event ids', function (): void {
    $store = analyticsServiceStore();
    $analytics = app(AnalyticsService::class);

    $first = $analytics->track(
        $store,
        AnalyticsEventType::PageView->value,
        ['url' => '/products/classic-cotton-t-shirt'],
        'session-analytics-1',
        null,
        'evt-analytics-1',
        now(),
    );

    $duplicate = $analytics->track(
        $store,
        AnalyticsEventType::PageView->value,
        ['url' => '/products/classic-cotton-t-shirt'],
        'session-analytics-1',
        null,
        'evt-analytics-1',
        now(),
    );

    expect($first)->toBeTrue()
        ->and($duplicate)->toBeFalse()
        ->and(AnalyticsEvent::withoutGlobalScopes()->where('client_event_id', 'evt-analytics-1')->count())->toBe(1);
});

test('analytics aggregation writes idempotent daily metrics', function (): void {
    $store = analyticsServiceStore();
    $analytics = app(AnalyticsService::class);
    $date = now()->subDays(10)->startOfDay();

    foreach (range(1, 5) as $index) {
        $analytics->track($store, AnalyticsEventType::PageView->value, ['url' => '/'], 'agg-session-'.($index % 3), null, "agg-page-{$index}", $date->copy()->addMinutes($index));
    }

    foreach (range(1, 3) as $index) {
        $analytics->track($store, AnalyticsEventType::AddToCart->value, ['variant_id' => $index], 'agg-session-'.$index, null, "agg-cart-{$index}", $date->copy()->addHour()->addMinutes($index));
    }

    foreach (range(1, 2) as $index) {
        $analytics->track($store, AnalyticsEventType::CheckoutStarted->value, ['cart_id' => $index], 'agg-session-'.$index, null, "agg-checkout-{$index}", $date->copy()->addHours(2)->addMinutes($index));
        $analytics->track($store, AnalyticsEventType::CheckoutCompleted->value, ['total_amount' => 5000 * $index], 'agg-session-'.$index, null, "agg-order-{$index}", $date->copy()->addHours(3)->addMinutes($index));
    }

    expect($analytics->aggregate($date))->toBe(1)
        ->and($analytics->aggregate($date))->toBe(1);

    $daily = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('date', $date->toDateString())
        ->firstOrFail();

    expect($daily->visits_count)->toBe(3)
        ->and($daily->add_to_cart_count)->toBe(3)
        ->and($daily->checkout_started_count)->toBe(2)
        ->and($daily->checkout_completed_count)->toBe(2)
        ->and($daily->orders_count)->toBe(2)
        ->and($daily->revenue_amount)->toBe(15000)
        ->and($daily->aov_amount)->toBe(7500);
});
