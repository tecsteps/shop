<?php

use App\Models\AnalyticsEvent;
use App\Models\Customer;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->baseUrl = 'http://'.$this->context['domain']->hostname.'/api/storefront/v1';
});

/**
 * A valid analytics event payload for the batch ingestion endpoint.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function analyticsEventPayload(array $overrides = []): array
{
    return array_merge([
        'type' => 'page_view',
        'session_id' => 'sess_abc123',
        'client_event_id' => 'evt_'.fake()->unique()->uuid(),
        'properties' => ['url' => '/'],
        'occurred_at' => now()->toIso8601String(),
    ], $overrides);
}

it('tracks a page view event', function () {
    $this->postJson("{$this->baseUrl}/analytics/events", [
        'events' => [analyticsEventPayload(['type' => 'page_view'])],
    ])
        ->assertStatus(202)
        ->assertJsonPath('accepted', 1)
        ->assertJsonPath('rejected', 0);

    $event = AnalyticsEvent::query()->withoutGlobalScopes()->where('type', 'page_view')->first();

    expect($event)->not->toBeNull();
    expect($event->store_id)->toBe($this->store->getKey());
});

it('tracks an add to cart event', function () {
    $this->postJson("{$this->baseUrl}/analytics/events", [
        'events' => [analyticsEventPayload([
            'type' => 'add_to_cart',
            'properties' => ['product_id' => 10, 'variant_id' => 101, 'quantity' => 1],
        ])],
    ])->assertStatus(202);

    $event = AnalyticsEvent::query()->withoutGlobalScopes()->where('type', 'add_to_cart')->firstOrFail();

    expect($event->properties_json['product_id'])->toBe(10);
    expect($event->properties_json['variant_id'])->toBe(101);
    expect($event->properties_json['quantity'])->toBe(1);
});

it('scopes events to current store', function () {
    $this->postJson("{$this->baseUrl}/analytics/events", [
        'events' => [analyticsEventPayload()],
    ])->assertStatus(202);

    $event = AnalyticsEvent::query()->withoutGlobalScopes()->firstOrFail();

    expect($event->store_id)->toBe($this->store->getKey());
});

it('includes session ID when available', function () {
    $this->postJson("{$this->baseUrl}/analytics/events", [
        'events' => [analyticsEventPayload(['session_id' => 'sess_with_id'])],
    ])->assertStatus(202);

    $event = AnalyticsEvent::query()->withoutGlobalScopes()->firstOrFail();

    expect($event->session_id)->toBe('sess_with_id');
});

it('includes customer ID when authenticated', function () {
    $customer = Customer::factory()->for($this->store)->create();

    actingAsCustomer($customer)
        ->postJson("{$this->baseUrl}/analytics/events", [
            'events' => [analyticsEventPayload()],
        ])->assertStatus(202);

    $event = AnalyticsEvent::query()->withoutGlobalScopes()->firstOrFail();

    expect($event->customer_id)->toBe($customer->getKey());
});
