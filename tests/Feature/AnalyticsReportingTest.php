<?php

use App\Livewire\Admin\Analytics\Index;
use App\Models\AnalyticsEvent;
use App\Models\Order;
use App\Models\User;
use App\Services\AnalyticsReportingService;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-09-23 12:00:00');
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

it('renders tenant-scoped sales, product, traffic, and conversion reports for the selected dates', function () {
    $store = shopStore();
    $otherStore = shopStore('analytics-report-other.test');
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    app()->instance('current_store', $store);
    $order = Order::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'order_number' => '#AN-1',
        'payment_method' => 'credit_card',
        'status' => 'paid',
        'financial_status' => 'paid',
        'currency' => 'EUR',
        'total_amount' => 1299,
        'placed_at' => '2026-09-23 10:00:00',
    ]);
    $order->lines()->create([
        'title_snapshot' => 'Main book',
        'quantity' => 2,
        'unit_price_amount' => 650,
        'total_amount' => 1299,
    ]);
    Order::withoutGlobalScopes()->create([
        'store_id' => $otherStore->id,
        'order_number' => '#OTHER-1',
        'payment_method' => 'credit_card',
        'status' => 'paid',
        'financial_status' => 'paid',
        'currency' => 'EUR',
        'total_amount' => 900000,
        'placed_at' => '2026-09-23 10:00:00',
    ]);

    foreach ([
        ['page_view', 'session-a', ['referrer' => 'https://search.example.test/', 'channel' => 'storefront', 'device' => 'desktop']],
        ['page_view', 'session-a', ['referrer' => 'https://search.example.test/', 'channel' => 'storefront', 'device' => 'desktop']],
        ['add_to_cart', 'session-a', ['channel' => 'storefront', 'device' => 'desktop']],
        ['checkout_started', 'session-a', ['channel' => 'storefront', 'device' => 'desktop']],
        ['checkout_completed', 'session-a', ['order_id' => $order->id, 'channel' => 'storefront', 'device' => 'desktop']],
    ] as $index => [$type, $sessionId, $properties]) {
        AnalyticsEvent::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'type' => $type,
            'session_id' => $sessionId,
            'client_event_id' => 'analytics-'.$index,
            'properties_json' => $properties,
            'occurred_at' => '2026-09-23 11:00:00',
            'created_at' => '2026-09-23 11:00:00',
        ]);
    }
    AnalyticsEvent::withoutGlobalScopes()->create([
        'store_id' => $otherStore->id,
        'type' => 'page_view',
        'session_id' => 'foreign-session',
        'client_event_id' => 'foreign-event',
        'properties_json' => ['referrer' => 'https://foreign.example.test/'],
        'occurred_at' => '2026-09-23 11:00:00',
        'created_at' => '2026-09-23 11:00:00',
    ]);

    $report = app(AnalyticsReportingService::class)->report(
        $store,
        CarbonImmutable::parse('2026-09-23 00:00:00', 'UTC'),
        CarbonImmutable::parse('2026-09-23 00:00:00', 'UTC'),
    );

    expect($report['top_referrers'][0])->toMatchArray([
        'source' => 'https://search.example.test/',
        'sessions' => 1,
        'orders' => 1,
        'conversion_rate' => 100.0,
    ]);

    Livewire::actingAs($user)->test(Index::class)
        ->set('dateRange', 'custom')
        ->set('customStartDate', '2026-09-23')
        ->set('customEndDate', '2026-09-23')
        ->assertSee('12.99 EUR')
        ->assertSee('Main book')
        ->assertSee('Rank')
        ->assertSee('https://search.example.test/')
        ->assertSee('Conversion rate')
        ->assertSee('100.00%')
        ->assertDontSee('Foreign');
});

it('applies device filters to storefront sessions and attributed orders', function () {
    $store = shopStore();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    app()->instance('current_store', $store);
    $order = Order::withoutGlobalScopes()->create([
        'store_id' => $store->id,
        'order_number' => '#AN-2',
        'payment_method' => 'credit_card',
        'status' => 'paid',
        'financial_status' => 'paid',
        'currency' => 'EUR',
        'total_amount' => 2500,
        'placed_at' => '2026-09-23 10:00:00',
    ]);

    foreach ([
        ['page_view', 'session-desktop', ['device' => 'desktop', 'channel' => 'storefront']],
        ['checkout_completed', 'session-desktop', ['order_id' => $order->id, 'device' => 'desktop', 'channel' => 'storefront']],
    ] as $index => [$type, $sessionId, $properties]) {
        AnalyticsEvent::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'type' => $type,
            'session_id' => $sessionId,
            'client_event_id' => 'filter-'.$index,
            'properties_json' => $properties,
            'occurred_at' => '2026-09-23 11:00:00',
            'created_at' => '2026-09-23 11:00:00',
        ]);
    }

    Livewire::actingAs($user)->test(Index::class)
        ->set('dateRange', 'custom')
        ->set('customStartDate', '2026-09-23')
        ->set('customEndDate', '2026-09-23')
        ->set('deviceFilter', 'mobile')
        ->assertSee('No sales in this period')
        ->assertSee('0.00 EUR');
});
