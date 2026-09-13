<?php

use App\Livewire\Admin\Analytics\Index;
use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    ['store' => $this->store, 'user' => $this->user] = $this->createStoreContext();
    $this->actingAsAdmin($this->user, $this->store);
});

it('renders the analytics page', function () {
    $this->get(route('admin.analytics.index'))->assertOk();
});

it('shows summary tiles from daily metrics', function () {
    AnalyticsDaily::factory()->create([
        'store_id' => $this->store->getKey(),
        'date' => today()->toDateString(),
        'orders_count' => 4,
        'revenue_amount' => 20000,
        'aov_amount' => 5000,
        'visits_count' => 100,
        'add_to_cart_count' => 20,
        'checkout_started_count' => 10,
    ]);

    Livewire::test(Index::class)
        ->assertViewHas('summary', fn ($summary) => $summary['orders_count'] === 4 && $summary['revenue_amount'] === 20000);
});

it('shows top referrers from page view events', function () {
    AnalyticsEvent::factory()->create([
        'store_id' => $this->store->getKey(),
        'type' => 'page_view',
        'session_id' => 'session-1',
        'properties_json' => ['referrer' => 'https://www.google.com/search'],
        'occurred_at' => now(),
        'created_at' => now(),
    ]);

    Livewire::test(Index::class)
        ->assertViewHas('topReferrers', fn ($referrers) => $referrers[0]['source'] === 'www.google.com');
});

it('exports a csv of daily metrics', function () {
    Storage::fake('local');
    AnalyticsDaily::factory()->create([
        'store_id' => $this->store->getKey(),
        'date' => today()->toDateString(),
    ]);

    $component = Livewire::test(Index::class)
        ->call('exportCsv')
        ->assertDispatched('toast');

    $path = $component->get('exportUrl');

    expect($path)->toEndWith('.csv');

    Storage::disk('local')->assertExists($path);
});
