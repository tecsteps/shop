<?php

use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function adminDashboardStore(): Store
{
    $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    app()->instance('current_store', $store);

    return $store;
}

function adminDashboardUser(): User
{
    return User::query()->where('email', 'admin@acme.test')->firstOrFail();
}

function adminDashboardOrder(Store $store, string $title, int $quantity, int $unitPrice, array $orderAttributes = []): Order
{
    $product = Product::factory()
        ->withDefaultVariant($unitPrice)
        ->create([
            'store_id' => $store->getKey(),
            'title' => $title,
        ]);
    $variant = ProductVariant::withoutGlobalScopes()
        ->where('product_id', $product->getKey())
        ->firstOrFail();
    $total = $quantity * $unitPrice;
    $order = Order::factory()->paid()->create(array_merge([
        'store_id' => $store->getKey(),
        'subtotal_amount' => $total,
        'shipping_amount' => 0,
        'tax_amount' => 0,
        'total_amount' => $total,
        'placed_at' => now(),
    ], $orderAttributes));

    OrderLine::factory()->create([
        'order_id' => $order->getKey(),
        'product_id' => $product->getKey(),
        'variant_id' => $variant->getKey(),
        'title_snapshot' => $title,
        'sku_snapshot' => 'DASH-'.str($title)->slug()->upper(),
        'quantity' => $quantity,
        'unit_price_amount' => $unitPrice,
        'total_amount' => $total,
    ]);

    return $order;
}

test('admin dashboard route requires authentication and renders store scoped metrics', function (): void {
    $store = adminDashboardStore();
    adminDashboardOrder($store, 'Dashboard Jacket', 2, 2500);
    adminDashboardOrder($store, 'Dashboard Cap', 1, 2000);

    $otherStore = Store::factory()->create();
    adminDashboardOrder($otherStore, 'Other Store Product', 1, 9900);

    $this->get('/admin')->assertRedirect('/admin/login');

    $this->actingAs(adminDashboardUser())
        ->withSession(['current_store_id' => $store->getKey()])
        ->get('/admin')
        ->assertSuccessful()
        ->assertSee('Dashboard')
        ->assertSee('70.00 EUR')
        ->assertSee('Dashboard Jacket')
        ->assertDontSee('Other Store Product');
});

test('admin dashboard recalculates kpis when date range changes', function (): void {
    $store = adminDashboardStore();
    $user = adminDashboardUser();

    adminDashboardOrder($store, 'Today Product', 1, 6000);
    adminDashboardOrder($store, 'Older Product', 1, 4000, [
        'placed_at' => now()->subDays(15),
    ]);

    Livewire::actingAs($user)
        ->test(AdminDashboard::class)
        ->assertSet('ordersCount', 2)
        ->assertSet('totalSales', 10000)
        ->set('dateRange', 'today')
        ->assertSet('ordersCount', 1)
        ->assertSet('totalSales', 6000)
        ->set('dateRange', 'custom')
        ->set('customStartDate', now()->subDays(20)->toDateString())
        ->set('customEndDate', now()->subDays(10)->toDateString())
        ->assertSet('ordersCount', 1)
        ->assertSet('totalSales', 4000);
});
