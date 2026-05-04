<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\StoreUserRole;
use App\Livewire\Admin\Orders\Index as AdminOrdersIndex;
use App\Livewire\Admin\Orders\Show as AdminOrderShow;
use App\Models\Fulfillment;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function adminOrderManagementStore(): Store
{
    $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    app()->instance('current_store', $store);

    return $store;
}

function adminOrderManagementUser(): User
{
    return User::query()->where('email', 'admin@acme.test')->firstOrFail();
}

function adminOrderManagementUserWithRole(Store $store, StoreUserRole $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);

    DB::table('store_users')->insert([
        'store_id' => $store->getKey(),
        'user_id' => $user->getKey(),
        'role' => $role->value,
        'created_at' => now(),
    ]);

    return $user;
}

/**
 * @return array{0: Order, 1: OrderLine, 2: ProductVariant}
 */
function adminOrderManagementOrder(Store $store, array $orderAttributes = [], int $quantity = 2, int $unitPrice = 2500): array
{
    $product = Product::factory()
        ->withDefaultVariant($unitPrice)
        ->create(['store_id' => $store->getKey()]);
    $variant = ProductVariant::withoutGlobalScopes()
        ->where('product_id', $product->getKey())
        ->firstOrFail();

    InventoryItem::withoutGlobalScopes()
        ->where('variant_id', $variant->getKey())
        ->update([
            'quantity_on_hand' => 10,
            'quantity_reserved' => 0,
        ]);

    $total = $quantity * $unitPrice;
    $order = Order::factory()->paid()->create(array_merge([
        'store_id' => $store->getKey(),
        'subtotal_amount' => $total,
        'shipping_amount' => 0,
        'tax_amount' => 0,
        'total_amount' => $total,
        'email' => 'buyer@example.test',
    ], $orderAttributes));
    $line = OrderLine::factory()->create([
        'order_id' => $order->getKey(),
        'product_id' => $product->getKey(),
        'variant_id' => $variant->getKey(),
        'title_snapshot' => 'Admin Test Product',
        'sku_snapshot' => 'ADMIN-TEST-001',
        'quantity' => $quantity,
        'unit_price_amount' => $unitPrice,
        'total_amount' => $total,
    ]);

    Payment::factory()->create([
        'order_id' => $order->getKey(),
        'method' => $order->payment_method,
        'status' => $order->financial_status === FinancialStatus::Pending ? PaymentStatus::Pending : PaymentStatus::Captured,
        'amount' => $total,
        'currency' => $order->currency,
    ]);

    return [$order, $line, $variant];
}

test('admin order routes require authentication and render scoped orders', function () {
    $store = adminOrderManagementStore();
    [$order] = adminOrderManagementOrder($store, [
        'order_number' => '#7001',
        'email' => 'alpha@example.test',
    ]);

    $otherStore = Store::factory()->create();
    adminOrderManagementOrder($otherStore, [
        'order_number' => '#9001',
        'email' => 'other@example.test',
    ]);

    $this->get('/admin/orders')->assertRedirect('/admin/login');

    $user = adminOrderManagementUser();

    $this->actingAs($user)
        ->get('/admin/orders')
        ->assertSuccessful()
        ->assertSee('#7001')
        ->assertDontSee('#9001');

    $this->actingAs($user)
        ->get('/admin/orders/'.$order->getKey())
        ->assertSuccessful()
        ->assertSee('#7001')
        ->assertSee('Admin Test Product');
});

test('admin order index filters by search and status dimensions', function () {
    $store = adminOrderManagementStore();
    $user = adminOrderManagementUser();
    adminOrderManagementOrder($store, [
        'order_number' => '#7001',
        'email' => 'alpha@example.test',
    ]);
    adminOrderManagementOrder($store, [
        'order_number' => '#7002',
        'email' => 'bravo@example.test',
        'status' => OrderStatus::Pending,
        'financial_status' => FinancialStatus::Pending,
        'payment_method' => PaymentMethod::BankTransfer,
    ]);

    Livewire::actingAs($user)
        ->test(AdminOrdersIndex::class)
        ->assertSee('#7001')
        ->assertSee('#7002')
        ->set('search', 'alpha')
        ->assertSee('#7001')
        ->assertDontSee('#7002')
        ->set('search', '')
        ->set('financialStatusFilter', 'pending')
        ->assertSee('#7002')
        ->assertDontSee('#7001');
});

test('admin order detail confirms bank transfer payments', function () {
    $store = adminOrderManagementStore();
    $user = adminOrderManagementUser();
    [$order, $line, $variant] = adminOrderManagementOrder($store, [
        'payment_method' => PaymentMethod::BankTransfer,
        'status' => OrderStatus::Pending,
        'financial_status' => FinancialStatus::Pending,
    ]);

    InventoryItem::withoutGlobalScopes()
        ->where('variant_id', $variant->getKey())
        ->update(['quantity_reserved' => $line->quantity]);

    Livewire::actingAs($user)
        ->test(AdminOrderShow::class, ['order' => $order])
        ->call('confirmBankTransferPayment')
        ->assertHasNoErrors();

    $inventory = InventoryItem::withoutGlobalScopes()->where('variant_id', $variant->getKey())->firstOrFail();

    expect($order->refresh()->status)->toBe(OrderStatus::Paid)
        ->and($order->financial_status)->toBe(FinancialStatus::Paid)
        ->and($order->payments()->first()?->status)->toBe(PaymentStatus::Captured)
        ->and($inventory->quantity_on_hand)->toBe(8)
        ->and($inventory->quantity_reserved)->toBe(0);
});

test('admin order detail processes refunds and fulfillment transitions', function () {
    $store = adminOrderManagementStore();
    $user = adminOrderManagementUser();
    [$order, $line] = adminOrderManagementOrder($store, quantity: 2, unitPrice: 2500);

    Livewire::actingAs($user)
        ->test(AdminOrderShow::class, ['order' => $order])
        ->set('refundAmount', '10.00')
        ->set('refundReason', 'Customer return')
        ->call('processRefund')
        ->assertHasNoErrors();

    expect($order->refresh()->financial_status)->toBe(FinancialStatus::PartiallyRefunded)
        ->and($order->refunds()->first()?->amount)->toBe(1000);

    Livewire::actingAs($user)
        ->test(AdminOrderShow::class, ['order' => $order->refresh()])
        ->set("fulfillmentLineQuantities.{$line->getKey()}", 1)
        ->set('trackingCompany', 'DHL')
        ->set('trackingNumber', 'DHL123')
        ->call('createFulfillment')
        ->assertHasNoErrors();

    $fulfillment = Fulfillment::query()->where('order_id', $order->getKey())->firstOrFail();

    expect($order->refresh()->fulfillment_status)->toBe(FulfillmentStatus::Partial)
        ->and($fulfillment->lines()->first()?->quantity)->toBe(1);

    Livewire::actingAs($user)
        ->test(AdminOrderShow::class, ['order' => $order->refresh()])
        ->call('markFulfillmentShipped', $fulfillment->getKey())
        ->call('markFulfillmentDelivered', $fulfillment->getKey())
        ->assertHasNoErrors();

    expect($fulfillment->refresh()->status)->toBe(FulfillmentShipmentStatus::Delivered)
        ->and($fulfillment->delivered_at)->not->toBeNull();
});

test('admin order detail enforces refund and fulfillment role policies', function (): void {
    $store = adminOrderManagementStore();
    $supportUser = adminOrderManagementUserWithRole($store, StoreUserRole::Support);
    $staffUser = adminOrderManagementUserWithRole($store, StoreUserRole::Staff);
    [$order, $line] = adminOrderManagementOrder($store, quantity: 2, unitPrice: 2500);

    Livewire::actingAs($supportUser)
        ->test(AdminOrderShow::class, ['order' => $order])
        ->set('refundAmount', '5.00')
        ->call('processRefund')
        ->assertStatus(403);

    Livewire::actingAs($staffUser)
        ->test(AdminOrderShow::class, ['order' => $order])
        ->set('refundAmount', '5.00')
        ->call('processRefund')
        ->assertStatus(403);

    Livewire::actingAs($staffUser)
        ->test(AdminOrderShow::class, ['order' => $order])
        ->set("fulfillmentLineQuantities.{$line->getKey()}", 1)
        ->call('createFulfillment')
        ->assertHasNoErrors();

    expect(Fulfillment::query()->where('order_id', $order->getKey())->exists())->toBeTrue()
        ->and($order->refresh()->refunds()->exists())->toBeFalse();
});

test('admin order detail rejects orders from another store', function () {
    $store = adminOrderManagementStore();
    $otherStore = Store::factory()->create();
    $user = adminOrderManagementUser();
    [$otherOrder] = adminOrderManagementOrder($otherStore, [
        'order_number' => '#9001',
    ]);

    app()->instance('current_store', $store);

    Livewire::actingAs($user)
        ->test(AdminOrderShow::class, ['order' => $otherOrder])
        ->assertStatus(404);
});
