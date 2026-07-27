<?php

use App\Livewire\Admin\Analytics\Index;
use App\Models\AnalyticsDaily;
use App\Models\SearchQuery;
use App\Services\AnalyticsService;
use Livewire\Livewire;

beforeEach(function () {
    $this->store = $this->createStore();
});

test('renders the analytics page with seeded daily data', function () {
    $user = $this->createUserWithRole($this->store, 'admin');

    AnalyticsDaily::factory()->create([
        'store_id' => $this->store->id,
        'date' => now()->subDays(2)->toDateString(),
        'orders_count' => 3,
        'revenue_amount' => 6000,
        'aov_amount' => 2000,
        'visits_count' => 60,
        'add_to_cart_count' => 12,
        'checkout_started_count' => 6,
        'checkout_completed_count' => 3,
    ]);

    AnalyticsDaily::factory()->create([
        'store_id' => $this->store->id,
        'date' => now()->subDay()->toDateString(),
        'orders_count' => 2,
        'revenue_amount' => 4000,
        'aov_amount' => 2000,
        'visits_count' => 40,
        'add_to_cart_count' => 8,
        'checkout_started_count' => 4,
        'checkout_completed_count' => 2,
    ]);

    $this->actingAs($user)
        ->withSession(['current_store_id' => $this->store->id])
        ->get('/admin/analytics')
        ->assertOk()
        ->assertSee('Analytics')
        ->assertSee('100.00 USD');

    $this->bindStore($this->store);

    Livewire::actingAs($user);
    Livewire::test(Index::class)
        ->assertViewHas('totalSales', 10000)
        ->assertViewHas('ordersCount', 5)
        ->assertViewHas('averageOrderValue', 2000)
        ->assertViewHas('conversionRate', 5.0)
        ->assertViewHas('funnel', fn (array $funnel): bool => $funnel[0]['count'] === 100
            && $funnel[1]['count'] === 20
            && $funnel[2]['count'] === 10
            && $funnel[3]['count'] === 5);
});

test('falls back to live event data when no daily rows exist', function () {
    $user = $this->createUserWithRole($this->store, 'owner');
    $analytics = app(AnalyticsService::class);

    $analytics->track($this->store, 'page_view', [], 'sess_1');
    $analytics->track($this->store, 'add_to_cart', ['product_id' => 1], 'sess_1');
    $analytics->track($this->store, 'checkout_started', [], 'sess_1');
    $analytics->track($this->store, 'checkout_completed', ['total' => 7500], 'sess_1');

    $this->bindStore($this->store);

    Livewire::actingAs($user);
    Livewire::test(Index::class)
        ->assertViewHas('totalSales', 7500)
        ->assertViewHas('ordersCount', 1)
        ->assertViewHas('averageOrderValue', 7500)
        ->assertViewHas('conversionRate', 100.0);
});

test('filters metrics by date range', function () {
    $user = $this->createUserWithRole($this->store, 'staff');

    AnalyticsDaily::factory()->create([
        'store_id' => $this->store->id,
        'date' => now()->subDays(10)->toDateString(),
        'orders_count' => 4,
        'revenue_amount' => 8000,
        'aov_amount' => 2000,
        'visits_count' => 80,
        'add_to_cart_count' => 0,
        'checkout_started_count' => 0,
        'checkout_completed_count' => 4,
    ]);

    AnalyticsDaily::factory()->create([
        'store_id' => $this->store->id,
        'date' => now()->toDateString(),
        'orders_count' => 1,
        'revenue_amount' => 2000,
        'aov_amount' => 2000,
        'visits_count' => 20,
        'add_to_cart_count' => 0,
        'checkout_started_count' => 0,
        'checkout_completed_count' => 1,
    ]);

    $this->bindStore($this->store);

    Livewire::actingAs($user);
    Livewire::test(Index::class)
        ->assertViewHas('totalSales', 10000)
        ->assertViewHas('ordersCount', 5)
        ->set('dateRange', 7)
        ->assertViewHas('totalSales', 2000)
        ->assertViewHas('ordersCount', 1);
});

test('staff can view analytics but support cannot', function (string $role, int $status) {
    $user = $this->createUserWithRole($this->store, $role);

    $this->actingAs($user)
        ->withSession(['current_store_id' => $this->store->id])
        ->get('/admin/analytics')
        ->assertStatus($status);
})->with([
    'owner' => ['owner', 200],
    'admin' => ['admin', 200],
    'staff' => ['staff', 200],
    'support' => ['support', 403],
]);

test('shows top products and recent searches', function () {
    $user = $this->createUserWithRole($this->store, 'admin');

    $product = \App\Models\Product::factory()->create(['store_id' => $this->store->id, 'title' => 'Blue Shirt']);

    $order = \App\Models\Order::factory()->create([
        'store_id' => $this->store->id,
        'placed_at' => now(),
    ]);

    \App\Models\OrderLine::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'title_snapshot' => 'Blue Shirt',
        'quantity' => 3,
        'unit_price_amount' => 2500,
        'total_amount' => 7500,
    ]);

    SearchQuery::factory()->create([
        'store_id' => $this->store->id,
        'query' => 'linen pants',
        'results_count' => 3,
    ]);

    $this->actingAs($user)
        ->withSession(['current_store_id' => $this->store->id])
        ->get('/admin/analytics')
        ->assertOk()
        ->assertSee('Blue Shirt')
        ->assertSee('75.00 USD')
        ->assertSee('linen pants');
});
