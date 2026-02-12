<?php

use App\Livewire\Admin\Analytics\Index;
use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Store;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function analyticsAdmin(): array
{
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    app()->instance('current_store', $store);

    return [$user, $store];
}

test('it renders the analytics index page', function () {
    [$user, $store] = analyticsAdmin();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('Analytics')
        ->assertSuccessful();
});

test('it displays page views for the selected period', function () {
    [$user, $store] = analyticsAdmin();

    AnalyticsDaily::create([
        'store_id' => $store->id,
        'date' => now()->subDays(5),
        'metric' => 'page_views',
        'value' => 150,
    ]);

    AnalyticsDaily::create([
        'store_id' => $store->id,
        'date' => now()->subDays(10),
        'metric' => 'page_views',
        'value' => 200,
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSet('period', 'last_30_days')
        ->assertSee('350');
});

test('it displays revenue data using revenue_amount metric', function () {
    [$user, $store] = analyticsAdmin();

    AnalyticsDaily::create([
        'store_id' => $store->id,
        'date' => now()->subDays(3),
        'metric' => 'revenue_amount',
        'value' => 5000,
    ]);

    AnalyticsDaily::create([
        'store_id' => $store->id,
        'date' => now()->subDays(7),
        'metric' => 'revenue_amount',
        'value' => 3000,
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertViewHas('totalRevenue', 8000);
});

test('it filters by period', function () {
    [$user, $store] = analyticsAdmin();

    AnalyticsDaily::create([
        'store_id' => $store->id,
        'date' => now()->subDays(5),
        'metric' => 'page_views',
        'value' => 100,
    ]);

    AnalyticsDaily::create([
        'store_id' => $store->id,
        'date' => now()->subDays(20),
        'metric' => 'page_views',
        'value' => 200,
    ]);

    // 7-day period should only show the first entry
    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('period', 'last_7_days')
        ->assertViewHas('pageViews', 100);
});

test('it displays top products from analytics events', function () {
    [$user, $store] = analyticsAdmin();

    AnalyticsEvent::create([
        'store_id' => $store->id,
        'event_type' => 'product_view',
        'properties_json' => ['product_title' => 'Red Sneakers'],
        'created_at' => now()->subDays(2),
    ]);

    AnalyticsEvent::create([
        'store_id' => $store->id,
        'event_type' => 'product_view',
        'properties_json' => ['product_title' => 'Red Sneakers'],
        'created_at' => now()->subDays(1),
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('Red Sneakers');
});

test('it does not show analytics from other stores', function () {
    [$user, $store] = analyticsAdmin();

    $otherStore = Store::factory()->create();

    AnalyticsDaily::create([
        'store_id' => $store->id,
        'date' => now()->subDays(1),
        'metric' => 'page_views',
        'value' => 100,
    ]);

    AnalyticsDaily::create([
        'store_id' => $otherStore->id,
        'date' => now()->subDays(1),
        'metric' => 'page_views',
        'value' => 9999,
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertViewHas('pageViews', 100);
});

test('it shows zeroes when no data exists', function () {
    [$user, $store] = analyticsAdmin();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertViewHas('pageViews', 0)
        ->assertViewHas('totalRevenue', 0);
});
