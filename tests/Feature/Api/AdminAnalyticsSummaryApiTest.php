<?php

use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function adminAnalyticsSummaryApiStore(): Store
{
    $store = Store::factory()->create();
    $user = adminAnalyticsSummaryApiUser();

    DB::table('store_users')->updateOrInsert(
        [
            'store_id' => $store->getKey(),
            'user_id' => $user->getKey(),
        ],
        [
            'role' => 'owner',
            'created_at' => now(),
        ],
    );

    return $store;
}

function adminAnalyticsSummaryApiUser(): User
{
    return User::query()->where('email', 'admin@acme.test')->firstOrFail();
}

/**
 * @param  list<string>  $abilities
 * @return array{token: \App\Models\PersonalAccessToken, plain_text: string}
 */
function adminAnalyticsSummaryApiToken(Store $store, array $abilities): array
{
    return adminApiToken($store, $abilities);
}

test('admin analytics summary api returns totals daily rows and top products', function (): void {
    $store = adminAnalyticsSummaryApiStore();
    $from = now()->subDay()->toDateString();
    $to = now()->toDateString();

    DB::table('analytics_daily')
        ->where('store_id', $store->getKey())
        ->whereBetween('date', [$from, $to])
        ->delete();

    DB::table('analytics_daily')->insert([
        [
            'store_id' => $store->getKey(),
            'date' => $from,
            'orders_count' => 2,
            'revenue_amount' => 2000,
            'aov_amount' => 1000,
            'visits_count' => 10,
            'add_to_cart_count' => 4,
            'checkout_started_count' => 3,
            'checkout_completed_count' => 2,
        ],
        [
            'store_id' => $store->getKey(),
            'date' => $to,
            'orders_count' => 3,
            'revenue_amount' => 9000,
            'aov_amount' => 3000,
            'visits_count' => 20,
            'add_to_cart_count' => 6,
            'checkout_started_count' => 7,
            'checkout_completed_count' => 3,
        ],
    ]);

    $product = Product::factory()
        ->for($store)
        ->withDefaultVariant(2000)
        ->create(['title' => 'Analytics API Jacket']);
    $order = Order::factory()->paid()->create([
        'store_id' => $store->getKey(),
        'currency' => $store->default_currency,
        'placed_at' => Carbon::parse($from)->addHours(12),
    ]);
    OrderLine::factory()->create([
        'order_id' => $order->getKey(),
        'product_id' => $product->getKey(),
        'title_snapshot' => $product->title,
        'quantity' => 4,
        'total_amount' => 8000,
    ]);

    $this->withToken(adminApiBearerToken($store, ['read-analytics'], adminAnalyticsSummaryApiUser()))
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/analytics/summary?from={$from}&to={$to}")
        ->assertOk()
        ->assertJsonPath('data.period.from', $from)
        ->assertJsonPath('data.period.to', $to)
        ->assertJsonPath('data.summary.orders_count', 5)
        ->assertJsonPath('data.summary.revenue_amount', 11000)
        ->assertJsonPath('data.summary.aov_amount', 2200)
        ->assertJsonPath('data.summary.visits_count', 30)
        ->assertJsonPath('data.summary.add_to_cart_count', 10)
        ->assertJsonPath('data.summary.checkout_started_count', 10)
        ->assertJsonPath('data.summary.conversion_rate', 0.1667)
        ->assertJsonPath('data.summary.currency', $store->default_currency)
        ->assertJsonPath('data.daily.0.date', $from)
        ->assertJsonPath('data.daily.1.date', $to)
        ->assertJsonPath('data.top_products.0.product_id', $product->getKey())
        ->assertJsonPath('data.top_products.0.title', 'Analytics API Jacket')
        ->assertJsonPath('data.top_products.0.units_sold', 4)
        ->assertJsonPath('data.top_products.0.revenue_amount', 8000);
});

test('admin analytics summary api enforces token abilities and store scope', function (): void {
    $store = adminAnalyticsSummaryApiStore();
    $otherStore = Store::factory()->create();
    $from = now()->subDay()->toDateString();
    $to = now()->toDateString();
    $readToken = adminAnalyticsSummaryApiToken($store, ['read-analytics']);
    $wrongAbilityToken = adminAnalyticsSummaryApiToken($store, ['read-settings']);
    $otherStoreToken = adminAnalyticsSummaryApiToken($otherStore, ['read-analytics']);

    $this->withToken($readToken['plain_text'])
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/analytics/summary?from={$from}&to={$to}")
        ->assertOk();

    $this->withToken($wrongAbilityToken['plain_text'])
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/analytics/summary?from={$from}&to={$to}")
        ->assertForbidden();

    $this->withToken($otherStoreToken['plain_text'])
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/analytics/summary?from={$from}&to={$to}")
        ->assertForbidden();
});

test('admin analytics summary api validates date ranges', function (): void {
    $store = adminAnalyticsSummaryApiStore();

    $this->withToken(adminApiBearerToken($store, ['read-analytics'], adminAnalyticsSummaryApiUser()))
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/analytics/summary?from=2026-02-10&to=2026-02-01")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['to']);

    $this->withToken(adminApiBearerToken($store, ['read-analytics'], adminAnalyticsSummaryApiUser()))
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/analytics/summary?from=2025-01-01&to=2026-02-01")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['to']);
});
