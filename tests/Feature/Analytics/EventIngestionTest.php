<?php

use App\Enums\AnalyticsEventType;
use App\Models\AnalyticsEvent;
use App\Models\Store;
use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('analytics tracking stores typed properties and deduplicates client event ids', function () {
    $store = Store::factory()->create();
    $analytics = app(AnalyticsService::class);

    $analytics->track(
        $store,
        'add_to_cart',
        ['variant_id' => 42, 'quantity' => 2],
        'session-1',
        null,
        'client-event-1',
    );
    $analytics->track($store, 'add_to_cart', [], 'session-1', null, 'client-event-1');

    $event = AnalyticsEvent::withoutGlobalScopes()->sole();

    expect($event->type)->toBe(AnalyticsEventType::AddToCart)
        ->and($event->properties_json)->toBe(['variant_id' => 42, 'quantity' => 2])
        ->and($event->store_id)->toBe($store->id);
});
