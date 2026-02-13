<?php

use App\Models\AnalyticsEvent;
use App\Models\Customer;
use App\Models\Store;
use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    $this->service = app(AnalyticsService::class);
});

test('tracks a page view event', function () {
    $this->service->track($this->store, 'page_view', ['url' => '/'], 'session-123');

    $event = AnalyticsEvent::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->first();

    expect($event)->not->toBeNull();
    expect($event->event_type)->toBe('page_view');
    expect($event->session_id)->toBe('session-123');
    expect($event->properties_json)->toBe(['url' => '/']);
});

test('tracks a product view event with properties', function () {
    $this->service->track($this->store, 'product_view', ['product_id' => 42, 'title' => 'Blue T-Shirt'], 'session-456');

    $event = AnalyticsEvent::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->first();

    expect($event->event_type)->toBe('product_view');
    expect($event->properties_json['product_id'])->toBe(42);
    expect($event->properties_json['title'])->toBe('Blue T-Shirt');
});

test('tracks add to cart event', function () {
    $this->service->track($this->store, 'add_to_cart', [
        'product_id' => 10,
        'variant_id' => 5,
        'quantity' => 2,
    ], 'session-789');

    $event = AnalyticsEvent::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->first();

    expect($event->event_type)->toBe('add_to_cart');
    expect($event->properties_json['quantity'])->toBe(2);
});

test('tracks checkout completed event', function () {
    $this->service->track($this->store, 'checkout_completed', [
        'order_id' => 1,
        'total_amount' => 5000,
    ], 'session-abc');

    $event = AnalyticsEvent::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->first();

    expect($event->event_type)->toBe('checkout_completed');
    expect($event->properties_json['total_amount'])->toBe(5000);
});

test('scopes events to current store', function () {
    $storeB = Store::factory()->create();

    $this->service->track($this->store, 'page_view', [], 'session-1');
    $this->service->track($storeB, 'page_view', [], 'session-2');

    $storeAEvents = AnalyticsEvent::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->count();

    $storeBEvents = AnalyticsEvent::withoutGlobalScopes()
        ->where('store_id', $storeB->id)
        ->count();

    expect($storeAEvents)->toBe(1);
    expect($storeBEvents)->toBe(1);
});

test('includes customer id when provided', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);

    $this->service->track($this->store, 'page_view', [], 'session-cust', $customer->id);

    $event = AnalyticsEvent::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->first();

    expect($event->customer_id)->toBe($customer->id);
});
