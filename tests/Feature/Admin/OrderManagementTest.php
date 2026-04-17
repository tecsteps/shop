<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\StoreUserRole;
use App\Livewire\Admin\Orders;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function setupAdminWithStore(StoreUserRole $role = StoreUserRole::Owner): array
{
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $store->users()->attach($user, ['role' => $role->value]);
    app()->instance('current_store', $store);
    session()->put('current_store_id', $store->id);

    return [$user, $store];
}

function createOrderWithLine(Store $store, array $overrides = []): Order
{
    $product = Product::factory()->create(['store_id' => $store->id, 'status' => 'active']);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500]);
    InventoryItem::factory()->withStock(50)->create(['store_id' => $store->id, 'variant_id' => $variant->id]);

    $order = Order::factory()->create(array_merge([
        'store_id' => $store->id,
        'total_amount' => 5000,
    ], $overrides));

    OrderLine::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'total_amount' => 5000,
    ]);

    Payment::factory()->create([
        'order_id' => $order->id,
        'amount' => 5000,
    ]);

    return $order;
}

it('lists orders for current store', function () {
    [$user, $store] = setupAdminWithStore();

    $order = createOrderWithLine($store);

    Livewire::actingAs($user)
        ->test(Orders\Index::class)
        ->assertSee($order->order_number);
});

it('shows order detail', function () {
    [$user, $store] = setupAdminWithStore();

    $order = createOrderWithLine($store);

    Livewire::actingAs($user)
        ->test(Orders\Show::class, ['order' => $order])
        ->assertSee($order->order_number)
        ->assertSee('Items');
});

it('creates fulfillment for paid order', function () {
    [$user, $store] = setupAdminWithStore();

    $order = createOrderWithLine($store, [
        'status' => OrderStatus::Paid,
        'financial_status' => FinancialStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Unfulfilled,
    ]);

    Livewire::actingAs($user)
        ->test(Orders\Show::class, ['order' => $order])
        ->set('trackingCompany', 'UPS')
        ->set('trackingNumber', '1Z999')
        ->call('createFulfillment')
        ->assertHasNoErrors();

    expect($order->fresh()->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled);
});

it('prevents fulfillment for unpaid order', function () {
    [$user, $store] = setupAdminWithStore();

    $order = createOrderWithLine($store, [
        'status' => OrderStatus::Pending,
        'financial_status' => FinancialStatus::Pending,
        'fulfillment_status' => FulfillmentStatus::Unfulfilled,
        'payment_method' => PaymentMethod::BankTransfer,
    ]);

    // Ensure payment is pending too
    $order->payments()->update(['status' => PaymentStatus::Pending]);

    Livewire::actingAs($user)
        ->test(Orders\Show::class, ['order' => $order])
        ->call('createFulfillment')
        ->assertHasErrors('fulfillment');
});

it('creates refund for paid order', function () {
    [$user, $store] = setupAdminWithStore();

    $order = createOrderWithLine($store, [
        'status' => OrderStatus::Paid,
        'financial_status' => FinancialStatus::Paid,
    ]);

    Livewire::actingAs($user)
        ->test(Orders\Show::class, ['order' => $order])
        ->set('refundAmount', 2000)
        ->set('refundReason', 'Customer request')
        ->call('createRefund')
        ->assertHasNoErrors();

    expect($order->fresh()->financial_status)->toBe(FinancialStatus::PartiallyRefunded);
});

it('cancels an order', function () {
    [$user, $store] = setupAdminWithStore();

    $order = createOrderWithLine($store, [
        'status' => OrderStatus::Paid,
        'financial_status' => FinancialStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Unfulfilled,
    ]);

    Livewire::actingAs($user)
        ->test(Orders\Show::class, ['order' => $order])
        ->call('cancelOrder')
        ->assertHasNoErrors();

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled);
});

it('confirms bank transfer payment', function () {
    [$user, $store] = setupAdminWithStore();

    $order = createOrderWithLine($store, [
        'status' => OrderStatus::Pending,
        'financial_status' => FinancialStatus::Pending,
        'payment_method' => PaymentMethod::BankTransfer,
    ]);

    $order->payments()->update([
        'status' => PaymentStatus::Pending,
        'method' => PaymentMethod::BankTransfer,
    ]);

    Livewire::actingAs($user)
        ->test(Orders\Show::class, ['order' => $order])
        ->call('confirmPayment')
        ->assertHasNoErrors();

    expect($order->fresh()->financial_status)->toBe(FinancialStatus::Paid);
});

it('denies order cancel for Staff role', function () {
    [$user, $store] = setupAdminWithStore(StoreUserRole::Support);

    $order = createOrderWithLine($store);

    Livewire::actingAs($user)
        ->test(Orders\Show::class, ['order' => $order])
        ->call('cancelOrder')
        ->assertForbidden();
});
