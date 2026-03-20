<?php

use App\Models\AnalyticsEvent;
use App\Models\Customer;
use App\Models\Store;
use App\Services\AnalyticsService;

beforeEach(function () {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
    $this->analyticsService = app(AnalyticsService::class);
});

it('tracks a page view event', function () {
    $this->analyticsService->track($this->store, 'page_view', ['url' => '/products'], 'session-1');

    $event = AnalyticsEvent::withoutGlobalScopes()->where('store_id', $this->store->id)->first();
    expect($event)->not->toBeNull()
        ->and($event->type)->toBe('page_view')
        ->and($event->session_id)->toBe('session-1')
        ->and($event->properties_json)->toBe(['url' => '/products']);
});

it('tracks a product view event', function () {
    $this->analyticsService->track($this->store, 'product_view', ['product_id' => 42], 'session-1');

    $event = AnalyticsEvent::withoutGlobalScopes()->where('store_id', $this->store->id)->first();
    expect($event->type)->toBe('product_view')
        ->and($event->properties_json['product_id'])->toBe(42);
});

it('tracks an add to cart event', function () {
    $this->analyticsService->track($this->store, 'add_to_cart', ['product_id' => 5, 'variant_id' => 10], 'session-1');

    $event = AnalyticsEvent::withoutGlobalScopes()->where('store_id', $this->store->id)->first();
    expect($event->type)->toBe('add_to_cart');
});

it('tracks checkout completed event', function () {
    $this->analyticsService->track($this->store, 'checkout_completed', ['order_total' => 5000], 'session-1');

    $event = AnalyticsEvent::withoutGlobalScopes()->where('store_id', $this->store->id)->first();
    expect($event->type)->toBe('checkout_completed')
        ->and($event->properties_json['order_total'])->toBe(5000);
});

it('ignores invalid event types', function () {
    $this->analyticsService->track($this->store, 'invalid_type', [], 'session-1');

    $count = AnalyticsEvent::withoutGlobalScopes()->where('store_id', $this->store->id)->count();
    expect($count)->toBe(0);
});

it('deduplicates events by client_event_id', function () {
    $this->analyticsService->track(
        $this->store, 'page_view', [], 'session-1', null, 'event-123'
    );
    $this->analyticsService->track(
        $this->store, 'page_view', [], 'session-1', null, 'event-123'
    );

    $count = AnalyticsEvent::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->count();
    expect($count)->toBe(1);
});

it('allows same client_event_id in different stores', function () {
    $otherStore = Store::factory()->create();

    $this->analyticsService->track($this->store, 'page_view', [], 'session-1', null, 'event-abc');
    $this->analyticsService->track($otherStore, 'page_view', [], 'session-2', null, 'event-abc');

    $count1 = AnalyticsEvent::withoutGlobalScopes()->where('store_id', $this->store->id)->count();
    $count2 = AnalyticsEvent::withoutGlobalScopes()->where('store_id', $otherStore->id)->count();

    expect($count1)->toBe(1)
        ->and($count2)->toBe(1);
});

it('tracks events with customer id', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);

    $this->analyticsService->track($this->store, 'page_view', [], 'session-1', $customer->id);

    $event = AnalyticsEvent::withoutGlobalScopes()->where('store_id', $this->store->id)->first();
    expect($event->customer_id)->toBe($customer->id);
});

it('processes a batch of events', function () {
    $this->analyticsService->trackBatch($this->store, [
        ['type' => 'page_view', 'session_id' => 's1', 'client_event_id' => 'e1', 'occurred_at' => '2026-03-20T10:00:00Z'],
        ['type' => 'product_view', 'session_id' => 's1', 'client_event_id' => 'e2', 'occurred_at' => '2026-03-20T10:01:00Z'],
        ['type' => 'add_to_cart', 'session_id' => 's1', 'client_event_id' => 'e3', 'occurred_at' => '2026-03-20T10:02:00Z'],
    ]);

    $count = AnalyticsEvent::withoutGlobalScopes()->where('store_id', $this->store->id)->count();
    expect($count)->toBe(3);
});

it('uses factory to create events', function () {
    $event = AnalyticsEvent::factory()->pageView()->create(['store_id' => $this->store->id]);

    expect($event->type)->toBe('page_view')
        ->and($event->store_id)->toBe($this->store->id);
});
