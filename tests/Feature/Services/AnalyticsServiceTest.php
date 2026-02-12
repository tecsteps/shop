<?php

use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Store;
use App\Services\AnalyticsService;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
    $this->service = new AnalyticsService;
});

test('track creates an analytics event record', function () {
    $this->service->track(
        $this->store,
        'page_view',
        ['url' => '/home'],
        'session-abc',
        42,
    );

    $this->assertDatabaseHas('analytics_events', [
        'store_id' => $this->store->id,
        'event_type' => 'page_view',
        'session_id' => 'session-abc',
        'customer_id' => 42,
    ]);

    $event = AnalyticsEvent::withoutGlobalScopes()->first();
    expect($event->properties_json)->toBe(['url' => '/home']);
});

test('track creates event without optional fields', function () {
    $this->service->track($this->store, 'search');

    $this->assertDatabaseHas('analytics_events', [
        'store_id' => $this->store->id,
        'event_type' => 'search',
        'session_id' => null,
        'customer_id' => null,
    ]);
});

test('getDailyMetrics returns aggregated data grouped by date', function () {
    $today = now()->toDateString();
    $yesterday = now()->subDay()->toDateString();

    AnalyticsDaily::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'date' => $today,
        'metric' => 'revenue_amount',
        'value' => 5000,
        'dimension' => null,
    ]);

    AnalyticsDaily::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'date' => $today,
        'metric' => 'page_views',
        'value' => 100,
        'dimension' => null,
    ]);

    AnalyticsDaily::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'date' => $yesterday,
        'metric' => 'revenue_amount',
        'value' => 3000,
        'dimension' => null,
    ]);

    // Dimension records should be excluded from getDailyMetrics
    AnalyticsDaily::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'date' => $today,
        'metric' => 'product_views',
        'value' => 10,
        'dimension' => 'Cool Widget',
    ]);

    $metrics = $this->service->getDailyMetrics(
        $this->store,
        now()->subDays(7)->toDateString(),
        $today,
    );

    expect($metrics)->toHaveCount(2)
        ->and($metrics[$today])->toHaveCount(2)
        ->and($metrics[$yesterday])->toHaveCount(1);
});

test('getDailyMetrics scopes to correct store', function () {
    $otherStore = Store::factory()->create();
    $today = now()->toDateString();

    AnalyticsDaily::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'date' => $today,
        'metric' => 'page_views',
        'value' => 50,
        'dimension' => null,
    ]);

    AnalyticsDaily::withoutGlobalScopes()->create([
        'store_id' => $otherStore->id,
        'date' => $today,
        'metric' => 'page_views',
        'value' => 200,
        'dimension' => null,
    ]);

    $metrics = $this->service->getDailyMetrics(
        $this->store,
        now()->subDays(7)->toDateString(),
        $today,
    );

    expect($metrics)->toHaveCount(1)
        ->and($metrics[$today]->first()->value)->toBe(50);
});

test('getTopProducts returns ordered list of products', function () {
    $now = now();

    // Create product_view events with different products
    for ($i = 0; $i < 5; $i++) {
        AnalyticsEvent::withoutGlobalScopes()->create([
            'store_id' => $this->store->id,
            'event_type' => 'product_view',
            'properties_json' => ['product_title' => 'Widget A'],
            'session_id' => 'session-'.$i,
            'created_at' => $now,
        ]);
    }

    for ($i = 0; $i < 3; $i++) {
        AnalyticsEvent::withoutGlobalScopes()->create([
            'store_id' => $this->store->id,
            'event_type' => 'product_view',
            'properties_json' => ['product_title' => 'Widget B'],
            'session_id' => 'session-b-'.$i,
            'created_at' => $now,
        ]);
    }

    AnalyticsEvent::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'event_type' => 'product_view',
        'properties_json' => ['product_title' => 'Widget C'],
        'session_id' => 'session-c',
        'created_at' => $now,
    ]);

    $topProducts = $this->service->getTopProducts(
        $this->store,
        now()->subDays(7)->toDateString(),
        now()->toDateString(),
    );

    expect($topProducts)->toHaveCount(3)
        ->and($topProducts[0]->product_title)->toBe('Widget A')
        ->and((int) $topProducts[0]->views)->toBe(5)
        ->and($topProducts[1]->product_title)->toBe('Widget B')
        ->and((int) $topProducts[1]->views)->toBe(3)
        ->and($topProducts[2]->product_title)->toBe('Widget C')
        ->and((int) $topProducts[2]->views)->toBe(1);
});

test('getTopProducts respects limit parameter', function () {
    $now = now();

    foreach (['Product A', 'Product B', 'Product C'] as $title) {
        AnalyticsEvent::withoutGlobalScopes()->create([
            'store_id' => $this->store->id,
            'event_type' => 'product_view',
            'properties_json' => ['product_title' => $title],
            'session_id' => 'session-'.strtolower($title),
            'created_at' => $now,
        ]);
    }

    $topProducts = $this->service->getTopProducts(
        $this->store,
        now()->subDays(7)->toDateString(),
        now()->toDateString(),
        2,
    );

    expect($topProducts)->toHaveCount(2);
});

test('getConversionFunnel returns correct counts', function () {
    $now = now();

    $eventCounts = [
        'page_view' => 100,
        'product_view' => 50,
        'add_to_cart' => 20,
        'checkout_started' => 10,
        'checkout_completed' => 5,
    ];

    foreach ($eventCounts as $type => $count) {
        for ($i = 0; $i < $count; $i++) {
            AnalyticsEvent::withoutGlobalScopes()->create([
                'store_id' => $this->store->id,
                'event_type' => $type,
                'properties_json' => [],
                'session_id' => 'session-'.$i,
                'created_at' => $now,
            ]);
        }
    }

    $funnel = $this->service->getConversionFunnel(
        $this->store,
        now()->subDays(7)->toDateString(),
        now()->toDateString(),
    );

    expect($funnel)->toBe([
        'page_views' => 100,
        'product_views' => 50,
        'add_to_cart' => 20,
        'checkout_started' => 10,
        'checkout_completed' => 5,
    ]);
});

test('getConversionFunnel returns zeros when no events exist', function () {
    $funnel = $this->service->getConversionFunnel(
        $this->store,
        now()->subDays(7)->toDateString(),
        now()->toDateString(),
    );

    expect($funnel)->toBe([
        'page_views' => 0,
        'product_views' => 0,
        'add_to_cart' => 0,
        'checkout_started' => 0,
        'checkout_completed' => 0,
    ]);
});
