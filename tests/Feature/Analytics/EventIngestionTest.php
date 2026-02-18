<?php

use App\Models\AnalyticsEvent;
use App\Models\Customer;
use App\Services\AnalyticsService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->analyticsService = app(AnalyticsService::class);
});

it('tracks a page view event', function () {
    $this->analyticsService->track($this->store, 'page_view', ['url' => '/']);

    $event = AnalyticsEvent::query()
        ->withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->first();

    expect($event)->not->toBeNull()
        ->and($event->type)->toBe('page_view')
        ->and($event->properties_json)->toBe(['url' => '/']);
});

it('tracks an add to cart event', function () {
    $this->analyticsService->track($this->store, 'add_to_cart', ['product_id' => 42]);

    $event = AnalyticsEvent::query()
        ->withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->first();

    expect($event)->not->toBeNull()
        ->and($event->type)->toBe('add_to_cart')
        ->and($event->properties_json['product_id'])->toBe(42);
});

it('scopes events to current store', function () {
    $this->analyticsService->track($this->store, 'page_view', []);

    $event = AnalyticsEvent::query()
        ->withoutGlobalScopes()
        ->first();

    expect($event->store_id)->toBe($this->store->id);
});

it('includes session ID when available', function () {
    $sessionId = 'test-session-123';

    $this->analyticsService->track($this->store, 'page_view', [], $sessionId);

    $event = AnalyticsEvent::query()
        ->withoutGlobalScopes()
        ->first();

    expect($event->session_id)->toBe($sessionId);
});

it('includes customer ID when authenticated', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);

    $this->analyticsService->track($this->store, 'page_view', [], null, $customer->id);

    $event = AnalyticsEvent::query()
        ->withoutGlobalScopes()
        ->first();

    expect($event->customer_id)->toBe($customer->id);
});
