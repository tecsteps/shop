<?php

use App\Jobs\AggregateAnalytics;
use App\Models\AnalyticsEvent;
use App\Models\Customer;
use App\Models\Store;
use App\Services\AnalyticsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Build the absolute URL of the ingestion endpoint for the store.
 */
function ingestionUrl(Store $store): string
{
    return 'http://'.$store->handle.'.test/api/storefront/v1/analytics/events';
}

/**
 * A valid event payload; override any field per test.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function ingestionEvent(array $overrides = []): array
{
    return array_merge([
        'type' => 'page_view',
        'session_id' => 'sess_'.Str::random(10),
        'client_event_id' => 'evt_'.Str::random(10),
        'occurred_at' => now()->toIso8601String(),
        'properties' => ['url' => '/products/classic-t-shirt'],
    ], $overrides);
}

beforeEach(function () {
    $this->store = $this->createStore();
    $this->bindStore($this->store);
});

test('accepts a valid batch of events', function () {
    $response = $this->postJson(ingestionUrl($this->store), [
        'events' => [
            ingestionEvent(['client_event_id' => 'evt_001']),
            ingestionEvent([
                'type' => 'add_to_cart',
                'client_event_id' => 'evt_002',
                'properties' => ['product_id' => 10, 'variant_id' => 101, 'quantity' => 1, 'price_amount' => 2500],
            ]),
        ],
    ]);

    $response->assertAccepted()
        ->assertJsonPath('accepted', 2)
        ->assertJsonPath('rejected', 0);

    $events = AnalyticsEvent::query()->orderBy('id')->get();

    expect($events)->toHaveCount(2)
        ->and($events[0]->type)->toBe('page_view')
        ->and($events[0]->store_id)->toBe($this->store->id)
        ->and($events[0]->properties_json['url'])->toBe('/products/classic-t-shirt')
        ->and($events[1]->type)->toBe('add_to_cart')
        ->and($events[1]->properties_json['variant_id'])->toBe(101);
});

test('silently drops duplicate client event ids', function () {
    $payload = ['events' => [ingestionEvent(['client_event_id' => 'evt_dup'])]];

    $this->postJson(ingestionUrl($this->store), $payload)->assertAccepted();
    // Duplicates are acknowledged: they count as accepted, not rejected.
    $this->postJson(ingestionUrl($this->store), $payload)
        ->assertAccepted()
        ->assertJsonPath('accepted', 1)
        ->assertJsonPath('rejected', 0);

    expect(AnalyticsEvent::query()->where('client_event_id', 'evt_dup')->count())->toBe(1);
});

test('scopes client event id uniqueness per store', function () {
    $otherStore = $this->createStore();

    $payload = ['events' => [ingestionEvent(['client_event_id' => 'evt_shared'])]];

    $this->postJson(ingestionUrl($this->store), $payload)->assertAccepted();
    $this->postJson(ingestionUrl($otherStore), $payload)->assertAccepted();

    $events = AnalyticsEvent::withoutGlobalScopes()
        ->where('client_event_id', 'evt_shared')
        ->pluck('store_id');

    expect($events)->toHaveCount(2)
        ->toContain($this->store->id, $otherStore->id);
});

test('rejects an invalid event type', function () {
    $this->postJson(ingestionUrl($this->store), [
        'events' => [ingestionEvent(['type' => 'purchase'])],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('events.0.type');
});

test('rejects timestamps outside the one hour window', function (string $when) {
    $occurredAt = $when === 'future'
        ? now()->addHours(2)->toIso8601String()
        : now()->subHours(2)->toIso8601String();

    $this->postJson(ingestionUrl($this->store), [
        'events' => [ingestionEvent(['occurred_at' => $occurredAt])],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('events.0.occurred_at');
})->with([
    'two hours in the future' => 'future',
    'two hours in the past' => 'past',
]);

test('rejects more than 50 events per batch', function () {
    $events = [];

    for ($i = 0; $i < 51; $i++) {
        $events[] = ingestionEvent();
    }

    $this->postJson(ingestionUrl($this->store), ['events' => $events])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('events');
});

test('rejects an empty batch', function () {
    $this->postJson(ingestionUrl($this->store), ['events' => []])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('events');
});

test('rejects properties nested deeper than three levels', function () {
    $this->postJson(ingestionUrl($this->store), [
        'events' => [ingestionEvent(['properties' => ['a' => ['b' => ['c' => ['d' => 1]]]]])],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('events.0.properties');
});

test('requires session id and client event id', function () {
    $event = ingestionEvent();
    unset($event['session_id'], $event['client_event_id']);

    $this->postJson(ingestionUrl($this->store), ['events' => [$event]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['events.0.session_id', 'events.0.client_event_id']);
});

test('tracks the store, session and customer through the service', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);

    app(AnalyticsService::class)->track(
        $this->store,
        'product_view',
        ['product_id' => 7],
        'sess_123',
        $customer->id,
    );

    $event = AnalyticsEvent::query()->sole();

    expect($event->store_id)->toBe($this->store->id)
        ->and($event->session_id)->toBe('sess_123')
        ->and($event->customer_id)->toBe($customer->id)
        ->and($event->type)->toBe('product_view');
});

test('aggregates events per day separately', function () {
    $analytics = app(AnalyticsService::class);

    $analytics->track($this->store, 'page_view', [], 'sess_a', null, null, now()->subDay()->toDateTimeString());
    $analytics->track($this->store, 'add_to_cart', [], 'sess_a', null, null, now()->subDay()->toDateTimeString());
    $analytics->track($this->store, 'checkout_completed', ['total' => 1000], 'sess_a', null, null, now()->subDay()->toDateTimeString());
    $analytics->track($this->store, 'checkout_completed', ['total' => 2500], 'sess_a', null, null, now()->toDateTimeString());

    (new AggregateAnalytics(now()->subDay()->toDateString()))->handle($analytics);
    (new AggregateAnalytics(now()->toDateString()))->handle($analytics);

    $rows = DB::table('analytics_daily')
        ->where('store_id', $this->store->id)
        ->orderBy('date')
        ->get()
        ->keyBy('date');

    $yesterday = $rows->get(now()->subDay()->toDateString());
    $today = $rows->get(now()->toDateString());

    expect($rows)->toHaveCount(2)
        ->and((int) $yesterday->orders_count)->toBe(1)
        ->and((int) $yesterday->revenue_amount)->toBe(1000)
        ->and((int) $yesterday->add_to_cart_count)->toBe(1)
        ->and((int) $today->orders_count)->toBe(1)
        ->and((int) $today->revenue_amount)->toBe(2500);
});
