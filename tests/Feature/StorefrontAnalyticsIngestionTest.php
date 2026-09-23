<?php

use App\Models\AnalyticsEvent;

it('stores canonical analytics properties and timestamps and drops duplicate client events per store', function () {
    $store = shopStore();
    $event = [
        'type' => 'product_view',
        'session_id' => 'browser-session-1',
        'client_event_id' => 'client-event-1',
        'properties' => ['product_id' => 42, 'url' => '/products/jacket'],
        'occurred_at' => now()->toIso8601String(),
    ];

    $firstResponse = $this->postJson('http://shop.test/api/storefront/v1/analytics/events', ['events' => [$event]]);
    $firstResponse->assertAccepted()->assertExactJson(['accepted' => 1, 'rejected' => 0]);

    $saved = AnalyticsEvent::withoutGlobalScopes()->where('store_id', $store->id)->where('client_event_id', 'client-event-1')->firstOrFail();
    expect($saved->properties_json)->toBe(['product_id' => 42, 'url' => '/products/jacket'])
        ->and($saved->occurred_at)->not->toBeNull();

    $duplicateResponse = $this->postJson('http://shop.test/api/storefront/v1/analytics/events', ['events' => [$event]]);
    $duplicateResponse->assertAccepted()->assertExactJson(['accepted' => 0, 'rejected' => 1]);
    expect(AnalyticsEvent::withoutGlobalScopes()->where('store_id', $store->id)->where('client_event_id', 'client-event-1')->count())->toBe(1);
});
