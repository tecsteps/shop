<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\StoreUserRole;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function ordersSetup(StoreUserRole $role = StoreUserRole::Owner): array
{
    $store = Store::factory()->create();
    $user = User::factory()->create(['email_verified_at' => now()]);
    DB::table('store_users')->insert([
        'store_id' => $store->getKey(),
        'user_id' => $user->getKey(),
        'role' => $role->value,
        'created_at' => now(),
    ]);
    session(['current_store_id' => $store->getKey()]);

    return [$store, $user];
}

function makeOrder(Store $store): Order
{
    return Order::factory()->create([
        'store_id' => $store->getKey(),
        'order_number' => '#2001',
        'status' => OrderStatus::Paid,
        'financial_status' => FinancialStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Unfulfilled,
        'payment_method' => PaymentMethod::CreditCard,
        'total_amount' => 5000,
        'currency' => 'USD',
        'email' => 'buyer@example.com',
        'placed_at' => now(),
    ]);
}

it('lists orders', function () {
    [$store, $user] = ordersSetup();
    makeOrder($store);

    $this->actingAs($user)->get('/admin/orders')
        ->assertOk()
        ->assertSee('#2001');
});

it('shows an order detail', function () {
    [$store, $user] = ordersSetup();
    $order = makeOrder($store);
    OrderLine::factory()->create([
        'order_id' => $order->getKey(),
        'title_snapshot' => 'Blue Shirt',
        'quantity' => 1,
        'unit_price_amount' => 5000,
        'total_amount' => 5000,
    ]);

    $this->actingAs($user)->get('/admin/orders/'.$order->getKey())
        ->assertOk()
        ->assertSee('#2001')
        ->assertSee('Blue Shirt');
});

it('denies staff from refunding an order', function () {
    [$store, $user] = ordersSetup(StoreUserRole::Staff);
    $order = makeOrder($store);
    Payment::factory()->create([
        'order_id' => $order->getKey(),
        'method' => PaymentMethod::CreditCard->value,
        'status' => PaymentStatus::Captured->value,
        'amount' => 5000,
        'currency' => 'USD',
    ]);

    $this->actingAs($user);

    Livewire\Livewire::test(\App\Livewire\Admin\Orders\Show::class, ['order' => $order->getKey()])
        ->set('refundAmount', 1000)
        ->call('createRefund')
        ->assertForbidden();
});
