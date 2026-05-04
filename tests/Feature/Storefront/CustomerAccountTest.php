<?php

use App\Livewire\Storefront\Account\Addresses\Index as AccountAddressesIndex;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Store;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function customerAccountStore(): Store
{
    $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();

    app()->instance('current_store', $store);

    return $store;
}

function customerAccountCustomer(): Customer
{
    $store = customerAccountStore();

    return Customer::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('email', 'customer@acme.test')
        ->firstOrFail();
}

test('customer dashboard renders the account overview', function (): void {
    $customer = customerAccountCustomer();

    $this->actingAs($customer, 'customer')
        ->get('http://shop.test/account')
        ->assertOk()
        ->assertSee('My Account')
        ->assertSee('John Doe')
        ->assertSee('Recent Orders');
});

test('unauthenticated customers are redirected to login', function (): void {
    $this->get('http://shop.test/account')
        ->assertRedirect('http://shop.test/account/login');
});

test('customer order history and detail are available through account routes', function (): void {
    $store = customerAccountStore();
    $customer = customerAccountCustomer();
    $orders = collect(['#1001', '#1002', '#1004'])->map(fn (string $orderNumber): Order => Order::factory()
        ->forCustomer($customer)
        ->paid()
        ->create([
            'store_id' => $store->getKey(),
            'customer_id' => $customer->getKey(),
            'order_number' => $orderNumber,
            'email' => $customer->email,
        ]));

    OrderLine::factory()->create([
        'order_id' => $orders->first()->getKey(),
        'title_snapshot' => 'Classic Cotton T-Shirt',
    ]);

    $this->actingAs($customer, 'customer')
        ->get('http://shop.test/account/orders')
        ->assertOk()
        ->assertSee('#1001')
        ->assertSee('#1002')
        ->assertSee('#1004');

    $this->actingAs($customer, 'customer')
        ->get('http://shop.test/account/orders/'.$orders->first()->getKey())
        ->assertOk()
        ->assertSee('#1001')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('Subtotal')
        ->assertSee('Total');
});

test('customer can add and update addresses', function (): void {
    $customer = customerAccountCustomer();

    $this->actingAs($customer, 'customer');

    Livewire::test(AccountAddressesIndex::class)
        ->assertSee('Main Street 1')
        ->call('openAddressForm')
        ->set('addressLabel', 'Office')
        ->set('address.first_name', 'John')
        ->set('address.last_name', 'Doe')
        ->set('address.address1', 'New Street 42')
        ->set('address.city', 'Hamburg')
        ->set('address.postal_code', '20095')
        ->set('address.country', 'DE')
        ->call('saveAddress')
        ->assertSee('Address saved')
        ->assertSee('New Street 42')
        ->assertSee('Hamburg');

    $address = CustomerAddress::query()
        ->where('customer_id', $customer->getKey())
        ->where('label', 'Office')
        ->firstOrFail();

    expect(data_get($address->address_json, 'city'))->toBe('Hamburg');

    Livewire::test(AccountAddressesIndex::class)
        ->call('openAddressForm', $address->getKey())
        ->set('address.city', 'Frankfurt')
        ->call('saveAddress')
        ->assertSee('Address saved')
        ->assertSee('Frankfurt');

    expect(data_get($address->refresh()->address_json, 'city'))->toBe('Frankfurt');
});

test('customer can set a default address and delete an address', function (): void {
    $customer = customerAccountCustomer();
    $homeAddress = CustomerAddress::query()
        ->where('customer_id', $customer->getKey())
        ->where('label', 'Home')
        ->firstOrFail();
    $officeAddress = CustomerAddress::factory()->create([
        'customer_id' => $customer->getKey(),
        'label' => 'Office',
        'address_json' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => 'Office Street 10',
            'address2' => null,
            'city' => 'Munich',
            'province_code' => null,
            'country' => 'DE',
            'postal_code' => '80331',
        ],
        'is_default' => false,
    ]);

    $this->actingAs($customer, 'customer');

    Livewire::test(AccountAddressesIndex::class)
        ->call('setDefaultAddress', $officeAddress->getKey())
        ->assertSee('Default address updated')
        ->call('deleteAddress', $officeAddress->getKey())
        ->assertSee('Address deleted');

    expect(CustomerAddress::query()->whereKey($officeAddress->getKey())->exists())->toBeFalse()
        ->and($homeAddress->refresh()->is_default)->toBeTrue();
});

test('customer logout clears the customer guard', function (): void {
    $customer = customerAccountCustomer();

    $this->actingAs($customer, 'customer')
        ->post('http://shop.test/account/logout')
        ->assertRedirect('http://shop.test/account/login');

    $this->assertGuest('customer');
});
