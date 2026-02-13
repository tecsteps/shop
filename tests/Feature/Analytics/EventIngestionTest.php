<?php

use App\Models\AnalyticsEvent;
use App\Models\Organization;
use App\Models\Store;
use App\Services\AnalyticsService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->analyticsService = new AnalyticsService;
});

it('tracks a page view event', function () {
    $this->analyticsService->track($this->store, 'page_view', ['url' => '/products']);

    $event = AnalyticsEvent::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->first();

    expect($event)->not->toBeNull()
        ->and($event->event_type)->toBe('page_view')
        ->and($event->properties_json)->toBe(['url' => '/products']);
});

it('tracks an add to cart event', function () {
    $this->analyticsService->track($this->store, 'add_to_cart', [
        'product_id' => 1,
        'variant_id' => 2,
        'quantity' => 1,
    ]);

    $event = AnalyticsEvent::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->first();

    expect($event)->not->toBeNull()
        ->and($event->event_type)->toBe('add_to_cart')
        ->and($event->properties_json['product_id'])->toBe(1);
});

it('scopes events to current store', function () {
    $org = Organization::factory()->create();
    $otherStore = Store::factory()->for($org)->create();

    $this->analyticsService->track($this->store, 'page_view');
    $this->analyticsService->track($otherStore, 'page_view');

    $storeEvents = AnalyticsEvent::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->count();

    $otherEvents = AnalyticsEvent::withoutGlobalScopes()
        ->where('store_id', $otherStore->id)
        ->count();

    expect($storeEvents)->toBe(1)
        ->and($otherEvents)->toBe(1);
});

it('includes session ID when available', function () {
    $this->analyticsService->track(
        $this->store,
        'page_view',
        [],
        'session-abc-123',
    );

    $event = AnalyticsEvent::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->first();

    expect($event->session_id)->toBe('session-abc-123');
});

it('includes customer ID when authenticated', function () {
    $this->analyticsService->track(
        $this->store,
        'product_view',
        ['product_id' => 5],
        'session-xyz',
        42,
    );

    $event = AnalyticsEvent::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->first();

    expect($event->customer_id)->toBe(42);
});
