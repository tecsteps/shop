<?php

use App\Models\AnalyticsEvent;
use App\Models\Customer;
use App\Services\AnalyticsService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->service = app(AnalyticsService::class);
});

it('tracks a page_view event', function () {
    $this->service->track($this->store, 'page_view', ['url' => '/'], 'sess-1');

    $event = AnalyticsEvent::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->first();

    expect($event)->not->toBeNull()
        ->and($event->type)->toBe('page_view')
        ->and($event->session_id)->toBe('sess-1')
        ->and($event->properties_json)->toBe(['url' => '/']);
});

it('tracks a product_view event with properties', function () {
    $this->service->track($this->store, 'product_view', ['product_id' => 42, 'handle' => 'test-shoe'], 'sess-2');

    $event = AnalyticsEvent::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('type', 'product_view')
        ->first();

    expect($event)->not->toBeNull()
        ->and($event->properties_json['product_id'])->toBe(42)
        ->and($event->properties_json['handle'])->toBe('test-shoe');
});

it('tracks events with a customer_id', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);

    $this->service->track($this->store, 'add_to_cart', ['variant_id' => 5], 'sess-3', $customer->id);

    $event = AnalyticsEvent::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->first();

    expect($event->customer_id)->toBe($customer->id);
});

it('tracks events without a session or customer', function () {
    $this->service->track($this->store, 'checkout_completed', ['order_id' => 10]);

    $event = AnalyticsEvent::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->first();

    expect($event->session_id)->toBeNull()
        ->and($event->customer_id)->toBeNull();
});

it('scopes events to the correct store', function () {
    $otherContext = createStoreContext('other-store.test');

    $this->service->track($this->store, 'page_view', [], 'sess-a');
    $this->service->track($otherContext['store'], 'page_view', [], 'sess-b');

    $storeEvents = AnalyticsEvent::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->count();

    $otherEvents = AnalyticsEvent::withoutGlobalScopes()
        ->where('store_id', $otherContext['store']->id)
        ->count();

    expect($storeEvents)->toBe(1)
        ->and($otherEvents)->toBe(1);
});

it('tracks all valid event types', function () {
    $types = ['page_view', 'product_view', 'add_to_cart', 'remove_from_cart', 'checkout_started', 'checkout_completed', 'search'];

    foreach ($types as $type) {
        $this->service->track($this->store, $type, ['test' => true]);
    }

    $count = AnalyticsEvent::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->count();

    expect($count)->toBe(7);
});

it('sets created_at timestamp automatically', function () {
    $this->service->track($this->store, 'page_view');

    $event = AnalyticsEvent::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->first();

    expect($event->created_at)->not->toBeNull();
});
