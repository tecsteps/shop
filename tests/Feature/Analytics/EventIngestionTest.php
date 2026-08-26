<?php

use App\Models\AnalyticsEvent;
use App\Services\AnalyticsService;

it('tracks a page view event', function () {
    $ctx = createStoreContext();

    app(AnalyticsService::class)->track($ctx['store'], 'page_view', [], 'sess_1', null, 'evt_1');

    expect(AnalyticsEvent::where('store_id', $ctx['store']->id)->where('type', 'page_view')->exists())->toBeTrue();
});

it('scopes events to current store', function () {
    $ctx = createStoreContext();

    app(AnalyticsService::class)->track($ctx['store'], 'page_view');

    expect(AnalyticsEvent::first()->store_id)->toBe($ctx['store']->id);
});

it('includes session ID when available', function () {
    $ctx = createStoreContext();

    app(AnalyticsService::class)->track($ctx['store'], 'page_view', [], 'sess_abc');

    expect(AnalyticsEvent::first()->session_id)->toBe('sess_abc');
});

it('includes customer ID when authenticated', function () {
    $ctx = createStoreContext();
    $customer = \App\Models\Customer::factory()->create(['store_id' => $ctx['store']->id]);

    app(AnalyticsService::class)->track($ctx['store'], 'add_to_cart', ['product_id' => 1], 'sess', $customer->id, 'evt_2');

    expect(AnalyticsEvent::first()->customer_id)->toBe($customer->id);
});
