<?php

use App\Livewire\Storefront\Account\Addresses\Index as AddressesIndex;
use App\Livewire\Storefront\Account\Dashboard;
use App\Livewire\Storefront\Account\Orders\Index as OrdersIndex;
use App\Livewire\Storefront\Account\Orders\Show as OrdersShow;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    StoreDomain::factory()->create([
        'store_id' => $this->store->id,
        'hostname' => 'shop.test',
    ]);
    app()->instance('current_store', $this->store);
    $this->customer = Customer::factory()->create([
        'store_id' => $this->store->id,
        'first_name' => 'Jane',
        'last_name' => 'Doe',
    ]);
    Auth::guard('customer')->login($this->customer);
});

test('dashboard shows customer name', function () {
    Livewire::test(Dashboard::class)
        ->assertSee('Welcome, Jane');
});

test('dashboard shows recent orders', function () {
    Order::factory()->count(3)->create([
        'store_id' => $this->store->id,
        'customer_id' => $this->customer->id,
    ]);

    Livewire::test(Dashboard::class)
        ->assertViewHas('recentOrders', fn ($orders) => $orders->count() === 3);
});

test('dashboard limits to 5 recent orders', function () {
    Order::factory()->count(7)->create([
        'store_id' => $this->store->id,
        'customer_id' => $this->customer->id,
    ]);

    Livewire::test(Dashboard::class)
        ->assertViewHas('recentOrders', fn ($orders) => $orders->count() === 5);
});

test('orders index shows paginated orders', function () {
    Order::factory()->count(3)->create([
        'store_id' => $this->store->id,
        'customer_id' => $this->customer->id,
    ]);

    Livewire::test(OrdersIndex::class)
        ->assertViewHas('orders', fn ($orders) => $orders->count() === 3);
});

test('order show displays order details', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'customer_id' => $this->customer->id,
        'order_number' => '1050',
    ]);
    OrderLine::factory()->create([
        'order_id' => $order->id,
        'title_snapshot' => 'Test Product',
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'total_amount' => 5000,
    ]);

    Livewire::test(OrdersShow::class, ['orderNumber' => '1050'])
        ->assertSee('Order #1050')
        ->assertSee('Test Product');
});

test('addresses index shows customer addresses', function () {
    CustomerAddress::factory()->count(2)->create([
        'customer_id' => $this->customer->id,
    ]);

    Livewire::test(AddressesIndex::class)
        ->assertViewHas('addresses', fn ($addresses) => $addresses->count() === 2);
});

test('can create a new address', function () {
    Livewire::test(AddressesIndex::class)
        ->call('openCreateModal')
        ->assertSet('showModal', true)
        ->set('first_name', 'Jane')
        ->set('last_name', 'Doe')
        ->set('address1', '123 Main St')
        ->set('city', 'Berlin')
        ->set('country', 'Germany')
        ->set('country_code', 'DE')
        ->set('zip', '10115')
        ->call('save')
        ->assertSet('showModal', false);

    expect(CustomerAddress::where('customer_id', $this->customer->id)->count())->toBe(1);
    expect(CustomerAddress::first()->is_default)->toBeTrue(); // First address is default
});

test('can edit an existing address', function () {
    $address = CustomerAddress::factory()->create([
        'customer_id' => $this->customer->id,
        'first_name' => 'Jane',
        'city' => 'Berlin',
    ]);

    Livewire::test(AddressesIndex::class)
        ->call('openEditModal', $address->id)
        ->assertSet('first_name', 'Jane')
        ->assertSet('city', 'Berlin')
        ->set('city', 'Munich')
        ->call('save');

    expect($address->fresh()->city)->toBe('Munich');
});

test('can delete an address', function () {
    $address = CustomerAddress::factory()->create([
        'customer_id' => $this->customer->id,
    ]);

    Livewire::test(AddressesIndex::class)
        ->call('delete', $address->id);

    expect(CustomerAddress::find($address->id))->toBeNull();
});

test('can set default address', function () {
    $address1 = CustomerAddress::factory()->create([
        'customer_id' => $this->customer->id,
        'is_default' => true,
    ]);
    $address2 = CustomerAddress::factory()->create([
        'customer_id' => $this->customer->id,
        'is_default' => false,
    ]);

    Livewire::test(AddressesIndex::class)
        ->call('setDefault', $address2->id);

    expect($address1->fresh()->is_default)->toBeFalse()
        ->and($address2->fresh()->is_default)->toBeTrue();
});

test('unauthenticated customer is redirected to login', function () {
    Auth::guard('customer')->logout();

    $response = $this->get('http://shop.test/account');

    $response->assertRedirect(route('storefront.account.login'));
});

// -- Dashboard additional tests --

test('dashboard has quick links to orders and addresses', function () {
    Livewire::test(Dashboard::class)
        ->assertSeeHtml(route('storefront.account.orders'))
        ->assertSeeHtml(route('storefront.account.addresses'))
        ->assertSee('Orders')
        ->assertSee('Addresses')
        ->assertSee('Log out');
});

test('dashboard shows empty state when no orders', function () {
    Livewire::test(Dashboard::class)
        ->assertViewHas('recentOrders', fn ($orders) => $orders->count() === 0);
});

test('dashboard only shows orders belonging to the customer', function () {
    $otherCustomer = Customer::factory()->create([
        'store_id' => $this->store->id,
    ]);

    Order::factory()->count(2)->create([
        'store_id' => $this->store->id,
        'customer_id' => $this->customer->id,
    ]);

    Order::factory()->count(3)->create([
        'store_id' => $this->store->id,
        'customer_id' => $otherCustomer->id,
    ]);

    Livewire::test(Dashboard::class)
        ->assertViewHas('recentOrders', fn ($orders) => $orders->count() === 2);
});

// -- Orders additional tests --

test('orders index shows empty state when no orders', function () {
    Livewire::test(OrdersIndex::class)
        ->assertSee('You have not placed any orders yet.');
});

test('orders index shows order numbers and totals', function () {
    Order::factory()->create([
        'store_id' => $this->store->id,
        'customer_id' => $this->customer->id,
        'order_number' => '2001',
        'total_amount' => 5497,
        'currency' => 'EUR',
    ]);

    Livewire::test(OrdersIndex::class)
        ->assertSee('#2001')
        ->assertSee('54.97')
        ->assertSee('EUR');
});

test('orders index only shows current customer orders', function () {
    $otherCustomer = Customer::factory()->create([
        'store_id' => $this->store->id,
    ]);

    Order::factory()->count(2)->create([
        'store_id' => $this->store->id,
        'customer_id' => $this->customer->id,
    ]);

    Order::factory()->count(3)->create([
        'store_id' => $this->store->id,
        'customer_id' => $otherCustomer->id,
    ]);

    Livewire::test(OrdersIndex::class)
        ->assertViewHas('orders', fn ($orders) => $orders->count() === 2);
});

test('order show displays line item quantities and prices', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'customer_id' => $this->customer->id,
        'order_number' => '3001',
        'subtotal_amount' => 5000,
        'shipping_amount' => 499,
        'tax_amount' => 798,
        'total_amount' => 6297,
        'currency' => 'EUR',
    ]);
    OrderLine::factory()->create([
        'order_id' => $order->id,
        'title_snapshot' => 'Blue T-Shirt',
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'total_amount' => 5000,
    ]);

    Livewire::test(OrdersShow::class, ['orderNumber' => '3001'])
        ->assertSee('Order #3001')
        ->assertSee('Blue T-Shirt')
        ->assertSee('25.00')
        ->assertSee('50.00')
        ->assertSee('Subtotal')
        ->assertSee('Shipping')
        ->assertSee('Tax')
        ->assertSee('Total')
        ->assertSee('62.97');
});

test('order show displays status badges', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'customer_id' => $this->customer->id,
        'order_number' => '3002',
        'status' => 'paid',
        'financial_status' => 'paid',
        'fulfillment_status' => 'unfulfilled',
    ]);

    Livewire::test(OrdersShow::class, ['orderNumber' => '3002'])
        ->assertSee('Paid')
        ->assertSee('Unfulfilled');
});

test('customer cannot view another customers order', function () {
    $otherCustomer = Customer::factory()->create([
        'store_id' => $this->store->id,
    ]);
    Order::factory()->create([
        'store_id' => $this->store->id,
        'customer_id' => $otherCustomer->id,
        'order_number' => '9999',
    ]);

    $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    Livewire::test(OrdersShow::class, ['orderNumber' => '9999']);
});

// -- Addresses additional tests --

test('address form validates required fields', function () {
    Livewire::test(AddressesIndex::class)
        ->call('openCreateModal')
        ->set('first_name', '')
        ->set('last_name', '')
        ->set('address1', '')
        ->set('city', '')
        ->set('country', '')
        ->set('country_code', '')
        ->set('zip', '')
        ->call('save')
        ->assertHasErrors(['first_name', 'last_name', 'address1', 'city', 'country', 'country_code', 'zip']);
});

test('address country code must be exactly 2 characters', function () {
    Livewire::test(AddressesIndex::class)
        ->call('openCreateModal')
        ->set('first_name', 'Jane')
        ->set('last_name', 'Doe')
        ->set('address1', '123 Main St')
        ->set('city', 'Berlin')
        ->set('country', 'Germany')
        ->set('country_code', 'DEU')
        ->set('zip', '10115')
        ->call('save')
        ->assertHasErrors('country_code');
});

test('cannot edit another customers address', function () {
    $otherCustomer = Customer::factory()->create([
        'store_id' => $this->store->id,
    ]);
    $otherAddress = CustomerAddress::factory()->create([
        'customer_id' => $otherCustomer->id,
    ]);

    $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    Livewire::test(AddressesIndex::class)
        ->call('openEditModal', $otherAddress->id);
});

test('cannot delete another customers address', function () {
    $otherCustomer = Customer::factory()->create([
        'store_id' => $this->store->id,
    ]);
    $otherAddress = CustomerAddress::factory()->create([
        'customer_id' => $otherCustomer->id,
    ]);

    $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    Livewire::test(AddressesIndex::class)
        ->call('delete', $otherAddress->id);
});

test('deleting default address promotes the next address', function () {
    $defaultAddress = CustomerAddress::factory()->create([
        'customer_id' => $this->customer->id,
        'is_default' => true,
    ]);
    $secondAddress = CustomerAddress::factory()->create([
        'customer_id' => $this->customer->id,
        'is_default' => false,
    ]);

    Livewire::test(AddressesIndex::class)
        ->call('delete', $defaultAddress->id);

    expect(CustomerAddress::find($defaultAddress->id))->toBeNull()
        ->and($secondAddress->fresh()->is_default)->toBeTrue();
});

test('addresses index shows empty state when no addresses', function () {
    Livewire::test(AddressesIndex::class)
        ->assertSee('You have not added any addresses yet.');
});

test('second address is not automatically set as default', function () {
    CustomerAddress::factory()->create([
        'customer_id' => $this->customer->id,
        'is_default' => true,
    ]);

    Livewire::test(AddressesIndex::class)
        ->call('openCreateModal')
        ->set('first_name', 'Jane')
        ->set('last_name', 'Doe')
        ->set('address1', '456 Oak Ave')
        ->set('city', 'Munich')
        ->set('country', 'Germany')
        ->set('country_code', 'DE')
        ->set('zip', '80331')
        ->call('save');

    $addresses = CustomerAddress::where('customer_id', $this->customer->id)
        ->orderBy('id')
        ->get();

    expect($addresses)->toHaveCount(2)
        ->and($addresses[0]->is_default)->toBeTrue()
        ->and($addresses[1]->is_default)->toBeFalse();
});
