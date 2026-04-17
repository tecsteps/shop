<?php

use App\Enums\AnalyticsEventType;
use App\Models\AnalyticsDaily;
use App\Models\Store;
use App\Services\AnalyticsService;
use Carbon\CarbonImmutable;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('aggregates yesterday events into analytics_daily via the command', function () {
    $store = Store::factory()->create();
    $yesterday = CarbonImmutable::yesterday();

    $service = app(AnalyticsService::class);

    CarbonImmutable::setTestNow($yesterday->setTime(12, 0));

    $service->track($store, AnalyticsEventType::PageView, [], 'sess-a');
    $service->track($store, AnalyticsEventType::PageView, [], 'sess-a');
    $service->track($store, AnalyticsEventType::PageView, [], 'sess-b');
    $service->track($store, AnalyticsEventType::AddToCart, ['variant_id' => 1], 'sess-a');
    $service->track($store, AnalyticsEventType::CheckoutStarted, [], 'sess-a');
    $service->track($store, AnalyticsEventType::CheckoutCompleted, [], 'sess-a');

    CarbonImmutable::setTestNow();

    $this->artisan('analytics:rollup')->assertExitCode(0);

    $row = AnalyticsDaily::query()
        ->withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('date', $yesterday->toDateString())
        ->first();

    expect($row)->not->toBeNull()
        ->and($row->visits_count)->toBe(2)
        ->and($row->add_to_cart_count)->toBe(1)
        ->and($row->checkout_started_count)->toBe(1)
        ->and($row->checkout_completed_count)->toBe(1);
});

it('upserts the row when run twice', function () {
    $store = Store::factory()->create();
    $yesterday = CarbonImmutable::yesterday();

    CarbonImmutable::setTestNow($yesterday->setTime(10, 0));
    app(AnalyticsService::class)->track($store, AnalyticsEventType::PageView, [], 'sess-x');
    CarbonImmutable::setTestNow();

    $this->artisan('analytics:rollup')->assertExitCode(0);
    $this->artisan('analytics:rollup')->assertExitCode(0);

    $count = AnalyticsDaily::query()
        ->withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('date', $yesterday->toDateString())
        ->count();

    expect($count)->toBe(1);
});

it('accepts an explicit --date option', function () {
    $store = Store::factory()->create();
    $target = CarbonImmutable::parse('2026-01-05');

    CarbonImmutable::setTestNow($target->setTime(8, 0));
    app(AnalyticsService::class)->track($store, AnalyticsEventType::PageView, [], 'sess-1');
    app(AnalyticsService::class)->track($store, AnalyticsEventType::AddToCart, [], 'sess-1');
    CarbonImmutable::setTestNow();

    $this->artisan('analytics:rollup', ['--date' => '2026-01-05'])->assertExitCode(0);

    $row = AnalyticsDaily::query()
        ->withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('date', '2026-01-05')
        ->first();

    expect($row)->not->toBeNull()
        ->and($row->visits_count)->toBe(1)
        ->and($row->add_to_cart_count)->toBe(1);
});
