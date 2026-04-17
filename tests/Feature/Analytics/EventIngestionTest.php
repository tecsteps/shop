<?php

use App\Models\AnalyticsEvent;
use App\Models\Store;
use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
});

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

it('tracks a page view event', function (): void {
    $event = app(AnalyticsService::class)->track($this->store, 'page_view', [], 'session-abc');

    expect($event)->toBeInstanceOf(AnalyticsEvent::class)
        ->and($event->type)->toBe('page_view')
        ->and($event->session_id)->toBe('session-abc')
        ->and($event->store_id)->toBe($this->store->id);
});

it('tracks an add_to_cart event with properties', function (): void {
    $properties = ['variant_id' => 42, 'quantity' => 2, 'client_event_id' => 'evt-xyz'];

    $event = app(AnalyticsService::class)->track($this->store, 'add_to_cart', $properties, 'session-xyz');

    expect($event->type)->toBe('add_to_cart')
        ->and($event->properties_json)->toBe($properties)
        ->and($event->client_event_id)->toBe('evt-xyz');
});
