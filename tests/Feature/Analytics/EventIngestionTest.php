<?php

use App\Models\Customer;

beforeEach(function () {
    $this->context = createStoreContext(['hostname' => 'shop.test']);
    $this->store = $this->context['store'];
});

/**
 * Build a single valid analytics event payload.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function analyticsEvent(array $overrides = []): array
{
    return array_merge([
        'type' => 'page_view',
        'session_id' => 'sess_'.uniqid(),
        'client_event_id' => 'evt_'.uniqid(),
        'properties' => ['url' => '/products/test'],
        'occurred_at' => now()->toISOString(),
    ], $overrides);
}

it('tracks a page view event', function () {
    $this->postJson(storefrontUrl('shop.test', '/api/storefront/v1/analytics/events'), [
        'events' => [analyticsEvent(['type' => 'page_view'])],
    ])->assertStatus(202)->assertJsonPath('accepted', 1);

    $this->assertDatabaseHas('analytics_events', [
        'store_id' => $this->store->id,
        'type' => 'page_view',
    ]);
});

it('tracks an add to cart event', function () {
    $this->postJson(storefrontUrl('shop.test', '/api/storefront/v1/analytics/events'), [
        'events' => [analyticsEvent([
            'type' => 'add_to_cart',
            'properties' => ['product_id' => 10, 'variant_id' => 101, 'quantity' => 1],
        ])],
    ])->assertStatus(202);

    $event = App\Models\AnalyticsEvent::query()->where('type', 'add_to_cart')->first();

    expect($event)->not->toBeNull()
        ->and($event->properties_json['product_id'])->toBe(10);
});

it('scopes events to current store', function () {
    $this->postJson(storefrontUrl('shop.test', '/api/storefront/v1/analytics/events'), [
        'events' => [analyticsEvent()],
    ])->assertStatus(202);

    $event = App\Models\AnalyticsEvent::query()->withoutGlobalScopes()->first();

    expect($event->store_id)->toBe($this->store->id);
});

it('includes session ID when available', function () {
    $this->postJson(storefrontUrl('shop.test', '/api/storefront/v1/analytics/events'), [
        'events' => [analyticsEvent(['session_id' => 'sess_known'])],
    ])->assertStatus(202);

    $this->assertDatabaseHas('analytics_events', [
        'store_id' => $this->store->id,
        'session_id' => 'sess_known',
    ]);
});

it('includes customer ID when authenticated', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);
    actingAsCustomer($customer);

    $this->postJson(storefrontUrl('shop.test', '/api/storefront/v1/analytics/events'), [
        'events' => [analyticsEvent()],
    ])->assertStatus(202);

    $this->assertDatabaseHas('analytics_events', [
        'store_id' => $this->store->id,
        'customer_id' => $customer->id,
    ]);
});

it('silently drops duplicate client event ids', function () {
    $payload = ['events' => [analyticsEvent(['client_event_id' => 'evt_dup'])]];

    $this->postJson(storefrontUrl('shop.test', '/api/storefront/v1/analytics/events'), $payload)
        ->assertStatus(202)->assertJsonPath('accepted', 1);

    $this->postJson(storefrontUrl('shop.test', '/api/storefront/v1/analytics/events'), $payload)
        ->assertStatus(202)->assertJsonPath('accepted', 0)->assertJsonPath('rejected', 1);

    expect(App\Models\AnalyticsEvent::query()->where('client_event_id', 'evt_dup')->count())->toBe(1);
});

it('rejects invalid event types', function () {
    $this->postJson(storefrontUrl('shop.test', '/api/storefront/v1/analytics/events'), [
        'events' => [analyticsEvent(['type' => 'not_a_real_type'])],
    ])->assertStatus(422);
});
