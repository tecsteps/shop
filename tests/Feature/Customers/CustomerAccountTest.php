<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderLine;
use Illuminate\Support\Facades\Hash;

it('redirects guests from the dashboard', function (): void {
    $this->createStoreContext(['hostname' => 'guard-store.test']);

    $response = $this->get('http://guard-store.test/account');

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('login');
});

it('renders dashboard and recent orders for authenticated customer', function (): void {
    $ctx = $this->createStoreContext(['hostname' => 'dash-store.test']);

    $customer = Customer::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'email' => 'dash@example.com',
        'password' => Hash::make('password'),
        'first_name' => 'Dash',
        'last_name' => 'User',
        'state' => 'active',
        'email_verified_at' => now(),
    ]);

    Order::factory()->create([
        'store_id' => $ctx['store']->id,
        'customer_id' => $customer->id,
        'order_number' => '100042',
        'status' => OrderStatus::Paid,
        'financial_status' => FinancialStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Unfulfilled,
        'payment_method' => PaymentMethod::CreditCard,
        'currency' => 'EUR',
        'total_amount' => 4200,
        'placed_at' => now(),
    ]);

    $this->actingAsCustomer($customer)
        ->get('http://dash-store.test/account')
        ->assertOk()
        ->assertSee('Recent orders')
        ->assertSee('#100042');
});

it('lists only the authenticated customer orders', function (): void {
    $ctx = $this->createStoreContext(['hostname' => 'orders-store.test']);

    $mine = Customer::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'email' => 'mine@example.com',
        'password' => Hash::make('password'),
        'state' => 'active',
    ]);

    $other = Customer::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'email' => 'other@example.com',
        'password' => Hash::make('password'),
        'state' => 'active',
    ]);

    Order::factory()->create([
        'store_id' => $ctx['store']->id,
        'customer_id' => $mine->id,
        'order_number' => 'MINE-1',
        'currency' => 'EUR',
        'total_amount' => 1000,
    ]);

    Order::factory()->create([
        'store_id' => $ctx['store']->id,
        'customer_id' => $other->id,
        'order_number' => 'OTHER-1',
        'currency' => 'EUR',
        'total_amount' => 2000,
    ]);

    $this->actingAsCustomer($mine)
        ->get('http://orders-store.test/account/orders')
        ->assertOk()
        ->assertSee('MINE-1')
        ->assertDontSee('OTHER-1');
});

it('returns 404 when opening another customer order', function (): void {
    $ctx = $this->createStoreContext(['hostname' => 'order-404.test']);

    $mine = Customer::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'email' => 'mine2@example.com',
        'password' => Hash::make('password'),
        'state' => 'active',
    ]);

    $other = Customer::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'email' => 'other2@example.com',
        'password' => Hash::make('password'),
        'state' => 'active',
    ]);

    $foreignOrder = Order::factory()->create([
        'store_id' => $ctx['store']->id,
        'customer_id' => $other->id,
        'order_number' => 'FOREIGN-1',
    ]);

    $this->actingAsCustomer($mine)
        ->get('http://order-404.test/account/orders/'.$foreignOrder->order_number)
        ->assertNotFound();
});

it('shows order detail with lines for the owner', function (): void {
    $ctx = $this->createStoreContext(['hostname' => 'order-show.test']);

    $customer = Customer::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'email' => 'show@example.com',
        'password' => Hash::make('password'),
        'state' => 'active',
    ]);

    $order = Order::factory()->create([
        'store_id' => $ctx['store']->id,
        'customer_id' => $customer->id,
        'order_number' => '200001',
        'currency' => 'EUR',
        'subtotal_amount' => 2500,
        'total_amount' => 3000,
        'shipping_amount' => 500,
    ]);

    OrderLine::factory()->create([
        'order_id' => $order->id,
        'title_snapshot' => 'Mocha Mug',
        'quantity' => 2,
        'unit_price_amount' => 1250,
        'total_amount' => 2500,
    ]);

    $this->actingAsCustomer($customer)
        ->get('http://order-show.test/account/orders/200001')
        ->assertOk()
        ->assertSee('Mocha Mug')
        ->assertSee('Order #200001');
});

it('logs the customer out via POST', function (): void {
    $ctx = $this->createStoreContext(['hostname' => 'logout-store.test']);

    $customer = Customer::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'email' => 'logout@example.com',
        'password' => Hash::make('password'),
        'state' => 'active',
    ]);

    $this->actingAsCustomer($customer)
        ->post('http://logout-store.test/account/logout')
        ->assertRedirect(route('account.login'));

    expect(auth('customer')->check())->toBeFalse();
});
