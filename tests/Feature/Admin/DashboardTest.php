<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Livewire\Admin\Dashboard;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function createAuthenticatedAdminWithStore(): array
{
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    app()->instance('current_store', $store);

    return [$user, $store];
}

test('dashboard displays KPI cards with correct data', function () {
    [$user, $store] = createAuthenticatedAdminWithStore();

    $customer = Customer::factory()->create(['store_id' => $store->id]);

    $order = Order::factory()->create([
        'store_id' => $store->id,
        'customer_id' => $customer->id,
        'total_amount' => 5000,
    ]);

    Payment::factory()->create([
        'order_id' => $order->id,
        'status' => PaymentStatus::Captured,
        'amount' => 5000,
        'captured_at' => now(),
    ]);

    Payment::factory()->pending()->create([
        'order_id' => $order->id,
        'amount' => 3000,
    ]);

    Product::factory()->count(3)->create(['store_id' => $store->id, 'status' => 'active']);
    Product::factory()->draft()->create(['store_id' => $store->id]);

    Livewire::actingAs($user)
        ->test(Dashboard::class)
        ->assertSee('Total Revenue')
        ->assertSee('50.00 EUR')
        ->assertSee('Total Orders')
        ->assertSee('Total Customers')
        ->assertSee('Active Products')
        ->assertSee('3');
});

test('dashboard displays recent orders table', function () {
    [$user, $store] = createAuthenticatedAdminWithStore();

    $customer = Customer::factory()->create([
        'store_id' => $store->id,
        'first_name' => 'Jane',
        'last_name' => 'Doe',
    ]);

    Order::factory()->create([
        'store_id' => $store->id,
        'customer_id' => $customer->id,
        'order_number' => '1042',
        'status' => OrderStatus::Paid,
        'total_amount' => 12345,
    ]);

    Livewire::actingAs($user)
        ->test(Dashboard::class)
        ->assertSee('1042')
        ->assertSee('Jane Doe')
        ->assertSee('123.45 EUR')
        ->assertSee('Paid');
});

test('dashboard shows orders and revenue for today', function () {
    [$user, $store] = createAuthenticatedAdminWithStore();

    $customer = Customer::factory()->create(['store_id' => $store->id]);

    $todayOrder = Order::factory()->create([
        'store_id' => $store->id,
        'customer_id' => $customer->id,
        'total_amount' => 2000,
        'created_at' => now(),
    ]);

    Payment::factory()->create([
        'order_id' => $todayOrder->id,
        'status' => PaymentStatus::Captured,
        'amount' => 2000,
        'captured_at' => now(),
    ]);

    $oldOrder = Order::factory()->create([
        'store_id' => $store->id,
        'customer_id' => $customer->id,
        'total_amount' => 5000,
        'created_at' => now()->subDays(5),
    ]);

    Payment::factory()->create([
        'order_id' => $oldOrder->id,
        'status' => PaymentStatus::Captured,
        'amount' => 5000,
        'captured_at' => now()->subDays(5),
    ]);

    Livewire::actingAs($user)
        ->test(Dashboard::class)
        ->assertSee('Today')
        ->assertSee('Orders today')
        ->assertSee('Revenue today')
        ->assertSee('20.00 EUR');
});

test('dashboard scopes data to the current store', function () {
    [$user, $store] = createAuthenticatedAdminWithStore();

    $otherStore = Store::factory()->create();

    Customer::factory()->count(2)->create(['store_id' => $store->id]);
    Customer::factory()->count(5)->create(['store_id' => $otherStore->id]);

    Order::factory()->create([
        'store_id' => $store->id,
        'customer_id' => Customer::factory()->create(['store_id' => $store->id])->id,
    ]);

    Order::factory()->count(3)->create([
        'store_id' => $otherStore->id,
        'customer_id' => Customer::factory()->create(['store_id' => $otherStore->id])->id,
    ]);

    Livewire::actingAs($user)
        ->test(Dashboard::class)
        ->assertSee('Recent Orders');
});

test('dashboard shows empty state when no orders exist', function () {
    [$user, $store] = createAuthenticatedAdminWithStore();

    Livewire::actingAs($user)
        ->test(Dashboard::class)
        ->assertSee('No orders yet.')
        ->assertSee('0.00 EUR');
});

test('dashboard formats money values correctly', function () {
    expect(Dashboard::formatMoney(0))->toBe('0.00 EUR')
        ->and(Dashboard::formatMoney(100))->toBe('1.00 EUR')
        ->and(Dashboard::formatMoney(12345))->toBe('123.45 EUR')
        ->and(Dashboard::formatMoney(999999))->toBe('9,999.99 EUR');
});
