<?php

use App\Enums\AnalyticsEventType;
use App\Models\AnalyticsDaily;
use App\Models\Store;
use App\Services\AnalyticsService;
use App\Services\DashboardMetricsService;
use Carbon\CarbonImmutable;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('returns the rolled-up row when present', function () {
    $store = Store::factory()->create();

    AnalyticsDaily::factory()->create([
        'store_id' => $store->getKey(),
        'date' => '2026-04-10',
        'orders_count' => 3,
        'revenue_amount' => 15_000,
        'aov_amount' => 5_000,
        'visits_count' => 42,
    ]);

    $metrics = app(DashboardMetricsService::class)->forDay($store, CarbonImmutable::parse('2026-04-10'));

    expect($metrics['orders_count'])->toBe(3)
        ->and($metrics['revenue_amount'])->toBe(15_000)
        ->and($metrics['visits_count'])->toBe(42);
});

it('falls back to live aggregation when no roll-up exists', function () {
    $store = Store::factory()->create();
    $date = CarbonImmutable::parse('2026-04-12');

    CarbonImmutable::setTestNow($date->setTime(14, 0));
    app(AnalyticsService::class)->track($store, AnalyticsEventType::PageView, [], 'sess-a');
    app(AnalyticsService::class)->track($store, AnalyticsEventType::PageView, [], 'sess-b');
    app(AnalyticsService::class)->track($store, AnalyticsEventType::AddToCart, [], 'sess-a');
    CarbonImmutable::setTestNow();

    $metrics = app(DashboardMetricsService::class)->forDay($store, $date);

    expect($metrics['visits_count'])->toBe(2)
        ->and($metrics['add_to_cart_count'])->toBe(1);
});
