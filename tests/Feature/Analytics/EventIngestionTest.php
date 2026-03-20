<?php

use App\Models\AnalyticsEvent;
use App\Models\Customer;
use App\Services\AnalyticsService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->service = new AnalyticsService;
});

it('tracks a page view event', function () {
    $this->service->track($this->store, 'page_view', [], 'sess-1');

    $event = AnalyticsEvent::withoutGlobalScopes()->where('store_id', $this->store->id)->first();

    expect($event)->not->toBeNull()
        ->and($event->type)->toBe('page_view')
        ->and($event->session_id)->toBe('sess-1')
        ->and($event->customer_id)->toBeNull();
});

it('tracks an event with customer association', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);

    $this->service->track($this->store, 'product_view', [], 'sess-1', $customer->id);

    $event = AnalyticsEvent::withoutGlobalScopes()->where('store_id', $this->store->id)->first();

    expect($event->customer_id)->toBe($customer->id);
});

it('stores event properties as JSON', function () {
    $this->service->track($this->store, 'product_view', ['product_id' => 42], 'sess-1');

    $event = AnalyticsEvent::withoutGlobalScopes()->where('store_id', $this->store->id)->first();

    expect($event->properties_json)->toBe(['product_id' => 42]);
});

it('scopes events to the correct store', function () {
    $otherContext = createStoreContext();
    $otherStore = $otherContext['store'];

    $this->service->track($this->store, 'page_view', [], 'sess-1');

    expect(AnalyticsEvent::withoutGlobalScopes()->where('store_id', $this->store->id)->count())->toBe(1)
        ->and(AnalyticsEvent::withoutGlobalScopes()->where('store_id', $otherStore->id)->count())->toBe(0);
});

it('tracks all supported event types', function () {
    $types = ['page_view', 'add_to_cart', 'checkout_started', 'checkout_completed'];

    foreach ($types as $type) {
        $this->service->track($this->store, $type, [], 'sess-1');
    }

    expect(AnalyticsEvent::withoutGlobalScopes()->where('store_id', $this->store->id)->count())->toBe(4);

    foreach ($types as $type) {
        expect(AnalyticsEvent::withoutGlobalScopes()
            ->where('store_id', $this->store->id)
            ->where('type', $type)
            ->exists())->toBeTrue();
    }
});
