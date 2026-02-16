<?php

use App\Models\AnalyticsDaily;
use App\Services\AnalyticsService;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->service = app(AnalyticsService::class);
});

it('retrieves daily metrics', function () {
    AnalyticsDaily::withoutGlobalScopes()->create([
        'store_id' => $this->ctx['store']->id,
        'date' => now()->toDateString(),
        'orders_count' => 5,
        'revenue_amount' => 50000,
        'aov_amount' => 10000,
        'visits_count' => 100,
        'add_to_cart_count' => 20,
        'checkout_started_count' => 10,
        'checkout_completed_count' => 5,
    ]);

    $metrics = $this->service->getDailyMetrics(
        $this->ctx['store'],
        now()->subDay()->toDateString(),
        now()->toDateString()
    );

    expect($metrics)->toHaveCount(1)
        ->and($metrics->first()->orders_count)->toBe(5);
});
