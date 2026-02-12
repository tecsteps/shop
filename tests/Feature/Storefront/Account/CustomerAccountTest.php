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
