<?php

use App\Jobs\AggregateAnalytics;
use App\Models\AnalyticsEvent;
use App\Models\Store;
use App\Models\User;
use App\Services\AnalyticsService;
use App\Services\ApiTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
    $this->seed();
    $this->store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $this->user = User::query()->where('email', 'admin@example.com')->firstOrFail();
});

test('storefront analytics api accepts batches and deduplicates client events', function (): void {
    $occurredAt = now()->toISOString();
    $payload = [
        'events' => [
            [
                'type' => 'page_view',
                'session_id' => 'sess-feature-analytics',
                'client_event_id' => 'evt-feature-page-view',
                'properties' => ['url' => '/products/linen-shirt'],
                'occurred_at' => $occurredAt,
            ],
            [
                'type' => 'checkout_completed',
                'session_id' => 'sess-feature-analytics',
                'client_event_id' => 'evt-feature-checkout',
                'properties' => ['order_id' => 999, 'total_amount' => 12900],
                'occurred_at' => $occurredAt,
            ],
        ],
    ];

    $this->postJson('http://shop.test/api/storefront/v1/analytics/events', $payload)
        ->assertStatus(202)
        ->assertJson([
            'accepted' => 2,
            'rejected' => 0,
        ]);

    $this->postJson('http://shop.test/api/storefront/v1/analytics/events', $payload)
        ->assertStatus(202)
        ->assertJson([
            'accepted' => 0,
            'rejected' => 0,
        ]);

    expect(AnalyticsEvent::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->whereIn('client_event_id', ['evt-feature-page-view', 'evt-feature-checkout'])
        ->count())->toBe(2);
});

test('analytics aggregation is idempotent for a daily metric row', function (): void {
    $analytics = app(AnalyticsService::class);
    $date = Carbon::parse('2026-03-20 12:00:00');

    $analytics->track($this->store, 'page_view', ['url' => '/'], 'sess-one', null, 'evt-agg-page-one', $date);
    $analytics->track($this->store, 'page_view', ['url' => '/products/linen-shirt'], 'sess-one', null, 'evt-agg-page-two', $date->copy()->addMinute());
    $analytics->track($this->store, 'page_view', ['url' => '/collections/summer-essentials'], 'sess-two', null, 'evt-agg-page-three', $date->copy()->addMinutes(2));
    $analytics->track($this->store, 'add_to_cart', ['variant_id' => 1], 'sess-two', null, 'evt-agg-cart', $date->copy()->addMinutes(3));
    $analytics->track($this->store, 'checkout_started', ['cart_total' => 14000], 'sess-two', null, 'evt-agg-started', $date->copy()->addMinutes(4));
    $analytics->track($this->store, 'checkout_completed', ['order_id' => 123, 'total_amount' => 14000], 'sess-two', null, 'evt-agg-checkout-one', $date->copy()->addMinutes(5));
    $analytics->track($this->store, 'checkout_completed', ['order_id' => 124, 'total_amount' => 6000], 'sess-three', null, 'evt-agg-checkout-two', $date->copy()->addMinutes(6));

    app(AggregateAnalytics::class, ['date' => $date->toDateString()])->handle($analytics);
    app(AggregateAnalytics::class, ['date' => $date->toDateString()])->handle($analytics);

    $metric = DB::table('analytics_daily')
        ->where('store_id', $this->store->id)
        ->where('date', $date->toDateString())
        ->first();

    expect($metric)->not->toBeNull()
        ->and($metric->orders_count)->toBe(2)
        ->and($metric->revenue_amount)->toBe(20000)
        ->and($metric->aov_amount)->toBe(10000)
        ->and($metric->visits_count)->toBe(2)
        ->and($metric->add_to_cart_count)->toBe(1)
        ->and($metric->checkout_started_count)->toBe(1)
        ->and($metric->checkout_completed_count)->toBe(2);
});

test('admin analytics summary api requires a store scoped token ability', function (): void {
    DB::table('analytics_daily')->updateOrInsert(
        [
            'store_id' => $this->store->id,
            'date' => '2026-04-01',
        ],
        [
            'orders_count' => 2,
            'revenue_amount' => 15000,
            'aov_amount' => 7500,
            'visits_count' => 20,
            'add_to_cart_count' => 5,
            'checkout_started_count' => 3,
            'checkout_completed_count' => 2,
        ],
    );

    $token = app(ApiTokenService::class)->create($this->store, $this->user, 'Analytics integration', ['read-analytics']);
    $url = route('api.admin.analytics.summary', $this->store).'?from=2026-04-01&to=2026-04-01';

    $this->getJson($url)->assertUnauthorized();

    $this->withToken($token['plain_text_token'])
        ->getJson($url)
        ->assertOk()
        ->assertJsonPath('data.summary.orders_count', 2)
        ->assertJsonPath('data.summary.revenue_amount', 15000)
        ->assertJsonPath('data.summary.currency', $this->store->default_currency);

    expect($token['token']->fresh()->last_used_at)->not->toBeNull();

    $wrongAbility = app(ApiTokenService::class)->create($this->store, $this->user, 'Catalog integration', ['read-products']);

    $this->withToken($wrongAbility['plain_text_token'])
        ->getJson($url)
        ->assertForbidden();
});
