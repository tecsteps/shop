<?php

use App\Enums\AnalyticsEventType;
use App\Jobs\AggregateAnalytics;
use App\Models\AnalyticsDaily;
use App\Services\AnalyticsService;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $context = $this->createStoreContext();
    $this->store = $context['store'];
    $this->analytics = app(AnalyticsService::class);
});

it('aggregates daily metrics from raw events', function (): void {
    $date = '2026-04-10';
    $start = $date.' 12:00:00';

    for ($i = 0; $i < 5; $i++) {
        DB::table('analytics_events')->insert([
            'store_id' => $this->store->id,
            'type' => AnalyticsEventType::PageView->value,
            'session_id' => "s-pv-{$i}",
            'properties_json' => '{}',
            'client_event_id' => "pv-{$i}",
            'occurred_at' => $start,
            'created_at' => $start,
        ]);
    }

    for ($i = 0; $i < 3; $i++) {
        DB::table('analytics_events')->insert([
            'store_id' => $this->store->id,
            'type' => AnalyticsEventType::AddToCart->value,
            'session_id' => "s-atc-{$i}",
            'properties_json' => '{}',
            'client_event_id' => "atc-{$i}",
            'occurred_at' => $start,
            'created_at' => $start,
        ]);
    }

    for ($i = 0; $i < 2; $i++) {
        DB::table('analytics_events')->insert([
            'store_id' => $this->store->id,
            'type' => AnalyticsEventType::CheckoutCompleted->value,
            'session_id' => "s-cc-{$i}",
            'properties_json' => '{}',
            'client_event_id' => "cc-{$i}",
            'occurred_at' => $start,
            'created_at' => $start,
        ]);
    }

    $row = $this->analytics->aggregate($this->store, $date);

    expect($row->add_to_cart_count)->toBe(3);
    expect($row->checkout_completed_count)->toBe(2);
    expect($row->visits_count)->toBe(10);
});

it('calculates revenue and AOV correctly', function (): void {
    $date = '2026-04-11';
    $placed = $date.' 12:00:00';

    foreach ([1000, 2000, 3000] as $i => $total) {
        DB::table('orders')->insert([
            'store_id' => $this->store->id,
            'order_number' => "ORD-{$date}-{$i}",
            'payment_method' => 'credit_card',
            'status' => 'confirmed',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'currency' => 'USD',
            'subtotal_amount' => $total,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => $total,
            'placed_at' => $placed,
            'created_at' => $placed,
            'updated_at' => $placed,
        ]);
    }

    $row = $this->analytics->aggregate($this->store, $date);

    expect($row->orders_count)->toBe(3);
    expect($row->revenue_amount)->toBe(6000);
    expect($row->aov_amount)->toBe(2000);
});

it('runs idempotently when aggregated twice', function (): void {
    $date = '2026-04-12';
    $placed = $date.' 12:00:00';

    DB::table('orders')->insert([
        'store_id' => $this->store->id,
        'order_number' => "ORD-{$date}-0",
        'payment_method' => 'credit_card',
        'status' => 'confirmed',
        'financial_status' => 'paid',
        'fulfillment_status' => 'unfulfilled',
        'currency' => 'USD',
        'subtotal_amount' => 5000,
        'discount_amount' => 0,
        'shipping_amount' => 0,
        'tax_amount' => 0,
        'total_amount' => 5000,
        'placed_at' => $placed,
        'created_at' => $placed,
        'updated_at' => $placed,
    ]);

    $this->analytics->aggregate($this->store, $date);
    $this->analytics->aggregate($this->store, $date);

    $rows = AnalyticsDaily::query()->withoutGlobalScopes()->where('store_id', $this->store->id)->where('date', $date)->get();
    expect($rows)->toHaveCount(1);
    expect($rows->first()->revenue_amount)->toBe(5000);
    expect($rows->first()->orders_count)->toBe(1);
});

it('processes all stores when run via the AggregateAnalytics job', function (): void {
    $date = '2026-04-13';
    $placed = $date.' 12:00:00';

    $other = $this->createStoreContext(['hostname' => 'other.test']);

    foreach ([$this->store, $other['store']] as $i => $store) {
        DB::table('orders')->insert([
            'store_id' => $store->id,
            'order_number' => "ORD-{$date}-{$i}",
            'payment_method' => 'credit_card',
            'status' => 'confirmed',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'currency' => 'USD',
            'subtotal_amount' => 1500,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 1500,
            'placed_at' => $placed,
            'created_at' => $placed,
            'updated_at' => $placed,
        ]);
    }

    (new AggregateAnalytics($date))->handle($this->analytics);

    $rows = AnalyticsDaily::query()->withoutGlobalScopes()->where('date', $date)->get();
    expect($rows)->toHaveCount(2);
    expect($rows->pluck('revenue_amount')->all())->toEqualCanonicalizing([1500, 1500]);
});

it('getDailyMetrics returns rows between start and end date inclusive', function (): void {
    foreach (['2026-04-01', '2026-04-05', '2026-04-10'] as $date) {
        AnalyticsDaily::query()->create([
            'store_id' => $this->store->id,
            'date' => $date,
            'orders_count' => 1,
            'revenue_amount' => 1000,
            'aov_amount' => 1000,
            'visits_count' => 5,
            'add_to_cart_count' => 2,
            'checkout_started_count' => 1,
            'checkout_completed_count' => 1,
        ]);
    }

    $rows = $this->analytics->getDailyMetrics($this->store, '2026-04-02', '2026-04-09');

    expect($rows)->toHaveCount(1);
    expect($rows->first()->date)->toBe('2026-04-05');
});
