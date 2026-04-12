<?php

use App\Livewire\Admin\Analytics\Index as AnalyticsIndex;
use App\Models\AnalyticsDaily;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

it('renders analytics for a date range', function (): void {
    [$user, $store] = loginAsAdmin();

    AnalyticsDaily::create([
        'store_id' => $store->id,
        'date' => now()->subDays(1)->toDateString(),
        'orders_count' => 5,
        'revenue_amount' => 25000,
        'aov_amount' => 5000,
        'visits_count' => 200,
        'add_to_cart_count' => 30,
        'checkout_started_count' => 10,
        'checkout_completed_count' => 5,
    ]);

    Livewire::test(AnalyticsIndex::class)
        ->assertSet('endDate', now()->toDateString())
        ->assertSee('Analytics')
        ->assertSee('Daily breakdown');
});
