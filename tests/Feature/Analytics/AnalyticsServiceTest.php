<?php

use App\Enums\AnalyticsEventType;
use App\Models\AnalyticsEvent;
use App\Models\Store;
use App\Services\AnalyticsService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('records an event with properties', function () {
    $store = Store::factory()->create();

    $event = app(AnalyticsService::class)->track(
        $store,
        AnalyticsEventType::PageView,
        ['path' => '/hats'],
        'sess-1',
    );

    expect($event)->not->toBeNull()
        ->and($event->type)->toBe(AnalyticsEventType::PageView->value)
        ->and($event->properties_json)->toBe(['path' => '/hats'])
        ->and($event->session_id)->toBe('sess-1')
        ->and($event->store_id)->toBe($store->getKey());
});

it('is idempotent for the same client_event_id per store', function () {
    $store = Store::factory()->create();
    $service = app(AnalyticsService::class);

    $first = $service->track($store, AnalyticsEventType::AddToCart, ['variant_id' => 3], null, null, 'cid-1');
    $duplicate = $service->track($store, AnalyticsEventType::AddToCart, ['variant_id' => 3], null, null, 'cid-1');

    expect($first)->not->toBeNull()
        ->and($duplicate)->toBeNull();

    $count = AnalyticsEvent::query()->withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('client_event_id', 'cid-1')
        ->count();

    expect($count)->toBe(1);
});

it('allows the same client_event_id in different stores', function () {
    $storeA = Store::factory()->create();
    $storeB = Store::factory()->create();
    $service = app(AnalyticsService::class);

    $a = $service->track($storeA, AnalyticsEventType::ProductView, [], null, null, 'shared-id');
    $b = $service->track($storeB, AnalyticsEventType::ProductView, [], null, null, 'shared-id');

    expect($a)->not->toBeNull()
        ->and($b)->not->toBeNull();
});

it('scopes read queries through BelongsToStore', function () {
    $storeA = Store::factory()->create();
    $storeB = Store::factory()->create();

    app(AnalyticsService::class)->track($storeA, AnalyticsEventType::PageView);
    app(AnalyticsService::class)->track($storeB, AnalyticsEventType::PageView);

    app()->instance('current_store', $storeA);

    $count = AnalyticsEvent::query()->count();

    expect($count)->toBe(1);
});
