<?php

use App\Models\Customer;
use App\Models\Organization;
use App\Models\Store;
use App\Services\AnalyticsService;

beforeEach(function () {
    $org = Organization::factory()->create();
    $this->store = Store::factory()->for($org)->create();
    app()->instance('current_store', $this->store);

    $this->service = new AnalyticsService;
});

test('track creates analytics event', function () {
    $event = $this->service->track($this->store, 'page_view', ['url' => '/products']);

    $this->assertDatabaseHas('analytics_events', [
        'store_id' => $this->store->id,
        'type' => 'page_view',
    ]);
    expect($event->properties_json)->toHaveKey('url');
});

test('track stores session id', function () {
    $event = $this->service->track($this->store, 'product_view', [], 'sess-123');

    expect($event->session_id)->toBe('sess-123');
});

test('track stores customer id', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);

    $event = $this->service->track($this->store, 'add_to_cart', ['variant_id' => 1], null, $customer->id);

    expect($event->customer_id)->toBe($customer->id);
});

test('track records different event types', function () {
    $types = ['page_view', 'product_view', 'add_to_cart', 'checkout_started', 'checkout_completed'];

    foreach ($types as $type) {
        $this->service->track($this->store, $type);
    }

    $this->assertDatabaseCount('analytics_events', 5);
});

test('events are scoped to store', function () {
    $otherOrg = Organization::factory()->create();
    $otherStore = Store::factory()->for($otherOrg)->create();

    $this->service->track($this->store, 'page_view');
    $this->service->track($otherStore, 'page_view');

    $storeEvents = \App\Models\AnalyticsEvent::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->count();

    expect($storeEvents)->toBe(1);
});
