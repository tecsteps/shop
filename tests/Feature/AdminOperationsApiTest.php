<?php

use App\Jobs\ReindexStoreProducts;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

function adminOperationsToken(User $user, int $storeId, array $abilities): string
{
    $plainTextToken = 'shop_'.Str::random(64);
    $user->tokens()->create([
        'store_id' => $storeId,
        'name' => 'admin-operations-test',
        'token' => hash('sha256', $plainTextToken),
        'abilities' => $abilities,
        'expires_at' => now()->addHour(),
    ]);

    return $plainTextToken;
}

function attachAdminRole(User $user, \App\Models\Store $store, string $role = 'owner'): void
{
    $user->stores()->attach($store->id, ['role' => $role]);
}

it('returns the authenticated store role and role-derived permission resource', function () {
    $store = shopStore();
    $user = User::factory()->create();
    attachAdminRole($user, $store, 'staff');
    $token = adminOperationsToken($user, $store->id, ['read-settings']);

    $this->withToken($token)->getJson("/api/admin/v1/stores/{$store->id}/me")
        ->assertOk()
        ->assertJsonPath('data.user_id', $user->id)
        ->assertJsonPath('data.store_id', $store->id)
        ->assertJsonPath('data.role', 'staff')
        ->assertJsonPath('data.permissions.0', 'read-products')
        ->assertJsonFragment(['read-analytics']);
});

it('queues store-scoped search reindexing and reports the documented index state', function () {
    Queue::fake();
    $store = shopStore();
    Product::factory()->create(['store_id' => $store->id]);
    $user = User::factory()->create();
    attachAdminRole($user, $store);
    $token = adminOperationsToken($user, $store->id, ['write-settings', 'read-settings']);
    $basePath = "/api/admin/v1/stores/{$store->id}/search";

    $this->withToken($token)->postJson("{$basePath}/reindex")
        ->assertAccepted()
        ->assertJsonPath('message', 'Reindex job queued.')
        ->assertJsonPath('status', 'queued')
        ->assertJsonStructure(['job_id']);
    Queue::assertPushed(ReindexStoreProducts::class, fn (ReindexStoreProducts $job): bool => $job->storeId === $store->id);

    $this->withToken($token)->postJson("{$basePath}/reindex")
        ->assertConflict();

    $this->withToken($token)->getJson("{$basePath}/status")
        ->assertOk()
        ->assertJsonPath('data.store_id', $store->id)
        ->assertJsonPath('data.index_status', 'queued')
        ->assertJsonPath('data.pending_updates', 1);
});

it('returns bounded analytics summaries, grouped periods, and top selling products', function () {
    $store = shopStore();
    $product = shopProduct($store);
    $user = User::factory()->create();
    attachAdminRole($user, $store);
    $token = adminOperationsToken($user, $store->id, ['read-analytics']);

    DB::table('analytics_daily')->insert([
        [
            'store_id' => $store->id,
            'date' => '2026-09-21',
            'orders_count' => 1,
            'revenue_amount' => 2500,
            'aov_amount' => 2500,
            'visits_count' => 10,
            'add_to_cart_count' => 4,
            'checkout_started_count' => 2,
            'checkout_completed_count' => 1,
        ],
        [
            'store_id' => $store->id,
            'date' => '2026-09-22',
            'orders_count' => 1,
            'revenue_amount' => 2500,
            'aov_amount' => 2500,
            'visits_count' => 20,
            'add_to_cart_count' => 3,
            'checkout_started_count' => 2,
            'checkout_completed_count' => 1,
        ],
    ]);
    $orderId = DB::table('orders')->insertGetId([
        'store_id' => $store->id,
        'order_number' => 'API-ANALYTICS-1',
        'payment_method' => 'credit_card',
        'status' => 'paid',
        'financial_status' => 'paid',
        'fulfillment_status' => 'unfulfilled',
        'currency' => 'EUR',
        'total_amount' => 2500,
        'placed_at' => '2026-09-21 12:00:00',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('order_lines')->insert([
        'order_id' => $orderId,
        'product_id' => $product['product']->id,
        'variant_id' => $product['variant']->id,
        'title_snapshot' => $product['product']->title,
        'quantity' => 1,
        'unit_price_amount' => 2500,
        'total_amount' => 2500,
        'tax_lines_json' => '[]',
        'discount_allocations_json' => '[]',
    ]);

    $this->withToken($token)->getJson("/api/admin/v1/stores/{$store->id}/analytics/summary?from=2026-09-21&to=2026-09-22&granularity=week")
        ->assertOk()
        ->assertJsonPath('data.period.from', '2026-09-21')
        ->assertJsonPath('data.summary.orders_count', 2)
        ->assertJsonPath('data.summary.revenue_amount', 5000)
        ->assertJsonPath('data.summary.aov_amount', 2500)
        ->assertJsonPath('data.summary.conversion_rate', 0.0667)
        ->assertJsonPath('data.summary.currency', 'EUR')
        ->assertJsonPath('data.daily.0.date', '2026-09-21')
        ->assertJsonPath('data.daily.0.visits_count', 30)
        ->assertJsonPath('data.top_products.0.product_id', $product['product']->id)
        ->assertJsonPath('data.top_products.0.units_sold', 1);

    $this->withToken($token)->getJson("/api/admin/v1/stores/{$store->id}/analytics/summary?from=2025-09-22&to=2026-09-23")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('to');
});
