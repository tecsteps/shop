<?php

use App\Models\AnalyticsEvent;
use App\Services\AnalyticsService;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->service = app(AnalyticsService::class);
});

it('tracks a page view event', function () {
    $this->service->track($this->ctx['store'], 'page_view');

    expect(AnalyticsEvent::withoutGlobalScopes()->where('type', 'page_view')->count())->toBe(1);
});

it('tracks an add to cart event', function () {
    $this->service->track($this->ctx['store'], 'add_to_cart', ['product_id' => 1]);

    $event = AnalyticsEvent::withoutGlobalScopes()->where('type', 'add_to_cart')->first();
    expect($event)->not->toBeNull()
        ->and($event->properties_json['product_id'])->toBe(1);
});

it('scopes events to current store', function () {
    $this->service->track($this->ctx['store'], 'page_view');

    $event = AnalyticsEvent::withoutGlobalScopes()->first();
    expect($event->store_id)->toBe($this->ctx['store']->id);
});

it('includes session ID when available', function () {
    $this->service->track($this->ctx['store'], 'page_view', [], 'test-session-id');

    $event = AnalyticsEvent::withoutGlobalScopes()->first();
    expect($event->session_id)->toBe('test-session-id');
});

it('includes customer ID when authenticated', function () {
    $customer = \App\Models\Customer::factory()->for($this->ctx['store'])->create();

    $this->service->track($this->ctx['store'], 'page_view', [], null, $customer->id);

    $event = AnalyticsEvent::withoutGlobalScopes()->first();
    expect($event->customer_id)->toBe($customer->id);
});
