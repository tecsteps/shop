<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Jobs\AggregateAnalytics;
use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->store = Store::factory()->create();
    $this->date = '2026-03-13';
});

it('aggregates daily metrics from events', function () {
    $dayStart = Carbon::parse($this->date)->startOfDay();

    $baseData = ['store_id' => $this->store->id];

    $event1 = AnalyticsEvent::withoutGlobalScopes()->create(array_merge($baseData, [
        'type' => 'page_view',
        'session_id' => 'sess-1',
    ]));
    $event1->forceFill(['created_at' => $dayStart->copy()->addHours(2)])->saveQuietly();

    $event2 = AnalyticsEvent::withoutGlobalScopes()->create(array_merge($baseData, [
        'type' => 'page_view',
        'session_id' => 'sess-2',
    ]));
    $event2->forceFill(['created_at' => $dayStart->copy()->addHours(3)])->saveQuietly();

    $event3 = AnalyticsEvent::withoutGlobalScopes()->create(array_merge($baseData, [
        'type' => 'add_to_cart',
        'session_id' => 'sess-1',
    ]));
    $event3->forceFill(['created_at' => $dayStart->copy()->addHours(4)])->saveQuietly();

    $event4 = AnalyticsEvent::withoutGlobalScopes()->create(array_merge($baseData, [
        'type' => 'checkout_started',
        'session_id' => 'sess-1',
    ]));
    $event4->forceFill(['created_at' => $dayStart->copy()->addHours(5)])->saveQuietly();

    $event5 = AnalyticsEvent::withoutGlobalScopes()->create(array_merge($baseData, [
        'type' => 'checkout_completed',
        'session_id' => 'sess-1',
    ]));
    $event5->forceFill(['created_at' => $dayStart->copy()->addHours(6)])->saveQuietly();

    $job = new AggregateAnalytics($this->date);
    $job->handle();

    $daily = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('date', $this->date)
        ->first();

    expect($daily)->not->toBeNull()
        ->and($daily->visits_count)->toBe(2)
        ->and($daily->add_to_cart_count)->toBe(1)
        ->and($daily->checkout_started_count)->toBe(1)
        ->and($daily->checkout_completed_count)->toBe(1);
});

it('calculates revenue and AOV from orders', function () {
    Order::withoutGlobalScopes()->insert([
        [
            'store_id' => $this->store->id,
            'order_number' => '9001',
            'email' => 'a@test.com',
            'status' => 'pending',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'payment_method' => 'credit_card',
            'currency' => 'EUR',
            'subtotal_amount' => 10000,
            'discount_amount' => 0,
            'shipping_amount' => 500,
            'tax_amount' => 1900,
            'total_amount' => 12400,
            'placed_at' => Carbon::parse($this->date)->addHours(10)->toDateTimeString(),
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ],
        [
            'store_id' => $this->store->id,
            'order_number' => '9002',
            'email' => 'b@test.com',
            'status' => 'pending',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'payment_method' => 'credit_card',
            'currency' => 'EUR',
            'subtotal_amount' => 5000,
            'discount_amount' => 0,
            'shipping_amount' => 500,
            'tax_amount' => 950,
            'total_amount' => 6450,
            'placed_at' => Carbon::parse($this->date)->addHours(14)->toDateTimeString(),
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ],
    ]);

    $job = new AggregateAnalytics($this->date);
    $job->handle();

    $daily = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('date', $this->date)
        ->first();

    expect($daily->orders_count)->toBe(2)
        ->and($daily->revenue_amount)->toBe(18850)
        ->and($daily->aov_amount)->toBe(9425);
});

it('is idempotent when run multiple times', function () {
    $event = AnalyticsEvent::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'type' => 'page_view',
        'session_id' => 'sess-1',
    ]);
    $event->forceFill(['created_at' => Carbon::parse($this->date)->addHours(2)])->saveQuietly();

    $job = new AggregateAnalytics($this->date);
    $job->handle();
    $job->handle();

    $count = AnalyticsDaily::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('date', $this->date)
        ->count();

    expect($count)->toBe(1);
});
