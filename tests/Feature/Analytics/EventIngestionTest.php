<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Models\AnalyticsEvent;
use App\Models\Customer;
use App\Models\Store;
use App\Services\AnalyticsService;

beforeEach(function () {
    $this->store = Store::factory()->create();
    $this->service = app(AnalyticsService::class);
});

it('tracks a page_view event', function () {
    $this->service->track($this->store, 'page_view', ['url' => '/']);

    expect(AnalyticsEvent::withoutGlobalScopes()->count())->toBe(1);

    $event = AnalyticsEvent::withoutGlobalScopes()->first();
    expect($event->type)->toBe('page_view')
        ->and($event->store_id)->toBe($this->store->id)
        ->and($event->properties_json)->toBe(['url' => '/']);
});

it('tracks an add_to_cart event', function () {
    $this->service->track($this->store, 'add_to_cart', ['variant_id' => 42]);

    $event = AnalyticsEvent::withoutGlobalScopes()->first();
    expect($event->type)->toBe('add_to_cart')
        ->and($event->properties_json)->toBe(['variant_id' => 42]);
});

it('scopes events to a store', function () {
    $otherStore = Store::factory()->create();

    $this->service->track($this->store, 'page_view');
    $this->service->track($otherStore, 'page_view');

    expect(AnalyticsEvent::withoutGlobalScopes()->where('store_id', $this->store->id)->count())->toBe(1)
        ->and(AnalyticsEvent::withoutGlobalScopes()->where('store_id', $otherStore->id)->count())->toBe(1);
});

it('includes session_id when provided', function () {
    $this->service->track($this->store, 'page_view', [], 'sess-abc-123');

    $event = AnalyticsEvent::withoutGlobalScopes()->first();
    expect($event->session_id)->toBe('sess-abc-123');
});

it('includes customer_id when provided', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);

    $this->service->track($this->store, 'page_view', [], null, $customer->id);

    $event = AnalyticsEvent::withoutGlobalScopes()->first();
    expect($event->customer_id)->toBe($customer->id);
});
