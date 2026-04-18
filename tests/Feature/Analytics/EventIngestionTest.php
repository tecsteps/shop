<?php

use App\Enums\AnalyticsEventType;
use App\Models\AnalyticsEvent;
use App\Models\Customer;
use App\Services\AnalyticsService;

beforeEach(function (): void {
    $context = $this->createStoreContext();
    $this->store = $context['store'];
    $this->analytics = app(AnalyticsService::class);
});

it('tracks a page view event', function (): void {
    $this->analytics->track($this->store, AnalyticsEventType::PageView);

    expect(AnalyticsEvent::query()->where('type', 'page_view')->count())->toBe(1);
});

it('tracks an add to cart event with properties', function (): void {
    $this->analytics->track($this->store, AnalyticsEventType::AddToCart, [
        'product_id' => 42,
        'quantity' => 2,
    ]);

    $event = AnalyticsEvent::query()->where('type', 'add_to_cart')->first();
    expect($event)->not->toBeNull();
    expect($event->properties_json)->toBe(['product_id' => 42, 'quantity' => 2]);
});

it('scopes events to the current store', function (): void {
    $other = $this->createStoreContext(['hostname' => 'other.test']);

    app()->instance('current_store', $this->store->fresh());
    $this->analytics->track($this->store, AnalyticsEventType::PageView);

    app()->instance('current_store', $other['store']->fresh());
    $this->analytics->track($other['store'], AnalyticsEventType::PageView);

    expect(AnalyticsEvent::query()->withoutGlobalScopes()->where('store_id', $this->store->id)->count())->toBe(1);
    expect(AnalyticsEvent::query()->withoutGlobalScopes()->where('store_id', $other['store']->id)->count())->toBe(1);
});

it('includes session id when available', function (): void {
    $this->analytics->track(
        $this->store,
        AnalyticsEventType::PageView,
        [],
        sessionId: 'sess-abc-123'
    );

    expect(AnalyticsEvent::query()->value('session_id'))->toBe('sess-abc-123');
});

it('includes customer id when authenticated', function (): void {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);

    $this->analytics->track(
        $this->store,
        AnalyticsEventType::ProductView,
        [],
        customerId: $customer->id,
    );

    expect(AnalyticsEvent::query()->value('customer_id'))->toBe($customer->id);
});

it('deduplicates events by client_event_id', function (): void {
    $this->analytics->track($this->store, AnalyticsEventType::PageView, clientEventId: 'evt-1');
    $this->analytics->track($this->store, AnalyticsEventType::PageView, clientEventId: 'evt-1');

    expect(AnalyticsEvent::query()->count())->toBe(1);
});
