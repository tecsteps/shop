<?php

use App\Enums\StoreUserRole;
use App\Livewire\Admin\Analytics\Index as AnalyticsIndex;
use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->user = $this->context['user'];
});

it('renders the analytics page', function () {
    actingAsAdmin($this->user)
        ->get('/admin/analytics')
        ->assertOk()
        ->assertSee('Analytics')
        ->assertSee('Conversion funnel')
        ->assertSee('Top products')
        ->assertSee('Top referrers');
});

it('shows KPIs from pre-aggregated daily metrics', function () {
    AnalyticsDaily::factory()->for($this->store)->onDate(now()->subDay()->toDateString())->create([
        'orders_count' => 4,
        'revenue_amount' => 20000,
        'visits_count' => 100,
    ]);
    AnalyticsDaily::factory()->for($this->store)->onDate(now()->subDays(2)->toDateString())->create([
        'orders_count' => 6,
        'revenue_amount' => 30000,
        'visits_count' => 100,
    ]);

    actingAsAdmin($this->user);

    Livewire::test(AnalyticsIndex::class)
        ->assertViewHas('ordersCount', 10)
        ->assertViewHas('totalSales', 50000)
        ->assertViewHas('averageOrderValue', 5000)
        ->assertViewHas('conversionRate', 5.0)
        ->assertSee('500.00 EUR');
});

it('filters metrics by date range', function () {
    AnalyticsDaily::factory()->for($this->store)->onDate(now()->subDays(2)->toDateString())->create([
        'orders_count' => 3,
        'revenue_amount' => 9000,
    ]);
    AnalyticsDaily::factory()->for($this->store)->onDate(now()->subDays(20)->toDateString())->create([
        'orders_count' => 5,
        'revenue_amount' => 25000,
    ]);

    actingAsAdmin($this->user);

    Livewire::test(AnalyticsIndex::class)
        ->assertViewHas('ordersCount', 8)
        ->set('dateRange', 'last_7_days')
        ->assertViewHas('ordersCount', 3)
        ->assertViewHas('totalSales', 9000);
});

it('builds the conversion funnel from raw events', function () {
    AnalyticsEvent::factory()->count(10)->pageView()->for($this->store)->create(['created_at' => now()->subDay()]);
    AnalyticsEvent::factory()->count(6)->productView()->for($this->store)->create(['created_at' => now()->subDay()]);
    AnalyticsEvent::factory()->count(4)->addToCart()->for($this->store)->create(['created_at' => now()->subDay()]);
    AnalyticsEvent::factory()->count(2)->for($this->store)->create(['type' => 'checkout_started', 'created_at' => now()->subDay()]);
    AnalyticsEvent::factory()->for($this->store)->create(['type' => 'checkout_completed', 'created_at' => now()->subDay()]);

    actingAsAdmin($this->user);

    Livewire::test(AnalyticsIndex::class)
        ->assertViewHas('funnel', function (array $funnel): bool {
            return array_column($funnel, 'count') === [10, 6, 4, 2, 1];
        });
});

it('allows owner, admin, and staff to view analytics', function (StoreUserRole $role) {
    $member = createStoreMember($this->store, $role);

    actingAsAdmin($member, $this->store)
        ->get('/admin/analytics')
        ->assertOk();
})->with([
    'admin' => StoreUserRole::Admin,
    'staff' => StoreUserRole::Staff,
]);

it('restricts analytics from the support role', function () {
    $support = createStoreMember($this->store, StoreUserRole::Support);

    actingAsAdmin($support, $this->store)
        ->get('/admin/analytics')
        ->assertForbidden();
});
