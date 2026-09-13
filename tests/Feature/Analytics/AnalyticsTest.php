<?php

use App\Jobs\AggregateAnalytics;
use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Services\AnalyticsService;
use Tests\Support\CatalogTestHelper;
use Tests\Support\CommerceTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(Illuminate\Routing\Middleware\ThrottleRequests::class);
});

it('accepts a batch of analytics events from the storefront', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();

    $response = $this->postJson(
        CommerceTestHelper::storefrontUrl($domain, '/api/storefront/v1/analytics/events'),
        ['events' => [
            [
                'type' => 'page_view',
                'session_id' => 'session-1',
                'client_event_id' => 'evt-1',
                'properties' => ['url' => '/', 'referrer' => null],
                'occurred_at' => now()->toIso8601String(),
            ],
            [
                'type' => 'add_to_cart',
                'session_id' => 'session-1',
                'client_event_id' => 'evt-2',
                'properties' => ['variant_id' => 1, 'quantity' => 2],
                'occurred_at' => now()->toIso8601String(),
            ],
        ]]
    );

    $response->assertAccepted();
    $response->assertJsonPath('accepted', 2);
    expect(AnalyticsEvent::query()->where('store_id', $store->getKey())->count())->toBe(2);
});

it('rejects invalid event types', function () {
    ['domain' => $domain] = $this->createStoreContext();

    $response = $this->postJson(
        CommerceTestHelper::storefrontUrl($domain, '/api/storefront/v1/analytics/events'),
        ['events' => [[
            'type' => 'not_a_real_event',
            'session_id' => 'session-1',
            'client_event_id' => 'evt-1',
            'occurred_at' => now()->toIso8601String(),
        ]]]
    );

    $response->assertUnprocessable();
});

it('rejects events with stale timestamps', function () {
    ['domain' => $domain] = $this->createStoreContext();

    $response = $this->postJson(
        CommerceTestHelper::storefrontUrl($domain, '/api/storefront/v1/analytics/events'),
        ['events' => [[
            'type' => 'page_view',
            'session_id' => 'session-1',
            'client_event_id' => 'evt-1',
            'occurred_at' => now()->subDays(2)->toIso8601String(),
        ]]]
    );

    $response->assertUnprocessable();
});

it('deduplicates events by client event id', function () {
    ['store' => $store] = $this->createStoreContext();
    $analytics = app(AnalyticsService::class);

    $first = $analytics->track($store, 'page_view', [], 'session-1', null, 'evt-dedupe');
    $second = $analytics->track($store, 'page_view', [], 'session-1', null, 'evt-dedupe');

    expect($first)->not->toBeNull()
        ->and($second)->toBeNull();
});

it('aggregates raw events into daily rows', function () {
    ['store' => $store] = $this->createStoreContext();
    $day = today()->subDay();

    AnalyticsEvent::factory()->create([
        'store_id' => $store->getKey(),
        'type' => 'page_view',
        'session_id' => 'session-1',
        'occurred_at' => $day->copy()->setTime(10, 0),
        'created_at' => $day->copy()->setTime(10, 0),
    ]);
    AnalyticsEvent::factory()->create([
        'store_id' => $store->getKey(),
        'type' => 'page_view',
        'session_id' => 'session-1',
        'occurred_at' => $day->copy()->setTime(11, 0),
        'created_at' => $day->copy()->setTime(11, 0),
    ]);
    AnalyticsEvent::factory()->create([
        'store_id' => $store->getKey(),
        'type' => 'add_to_cart',
        'session_id' => 'session-1',
        'occurred_at' => $day->copy()->setTime(12, 0),
        'created_at' => $day->copy()->setTime(12, 0),
    ]);
    AnalyticsEvent::factory()->create([
        'store_id' => $store->getKey(),
        'type' => 'checkout_completed',
        'session_id' => 'session-1',
        'properties_json' => ['total_amount' => 5000],
        'occurred_at' => $day->copy()->setTime(13, 0),
        'created_at' => $day->copy()->setTime(13, 0),
    ]);

    (new AggregateAnalytics($day->toDateString()))->handle();

    $daily = AnalyticsDaily::query()
        ->where('store_id', $store->getKey())
        ->where('date', $day->toDateString())
        ->firstOrFail();

    expect($daily->visits_count)->toBe(1)
        ->and($daily->add_to_cart_count)->toBe(1)
        ->and($daily->orders_count)->toBe(1)
        ->and($daily->revenue_amount)->toBe(5000)
        ->and($daily->aov_amount)->toBe(5000);
});

it('serves the analytics summary over the admin api', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();
    AnalyticsDaily::factory()->create([
        'store_id' => $store->getKey(),
        'date' => today()->toDateString(),
        'orders_count' => 3,
        'revenue_amount' => 15000,
        'aov_amount' => 5000,
        'visits_count' => 60,
        'add_to_cart_count' => 12,
        'checkout_started_count' => 6,
    ]);

    $response = $this->getJson(
        "/api/admin/v1/stores/{$store->getKey()}/analytics/summary?from=".today()->toDateString().'&to='.today()->toDateString(),
        CatalogTestHelper::adminHeaders($user, ['read-analytics'])
    );

    $response->assertOk();
    $response->assertJsonPath('data.summary.orders_count', 3);
    $response->assertJsonPath('data.summary.revenue_amount', 15000);
    $response->assertJsonPath('data.summary.conversion_rate', 0.05);
});

it('rejects summary ranges over 365 days', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();

    $response = $this->getJson(
        "/api/admin/v1/stores/{$store->getKey()}/analytics/summary?from=2024-01-01&to=2026-01-01",
        CatalogTestHelper::adminHeaders($user, ['read-analytics'])
    );

    $response->assertUnprocessable();
});

it('requires the read-analytics ability for the summary', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();

    $response = $this->getJson(
        "/api/admin/v1/stores/{$store->getKey()}/analytics/summary?from=".today()->toDateString().'&to='.today()->toDateString(),
        CatalogTestHelper::adminHeaders($user, ['read-products'])
    );

    $response->assertForbidden();
});
