<?php

use App\Livewire\Storefront\Account\Addresses\Index as AddressIndex;
use App\Livewire\Storefront\Account\Auth\Login;
use App\Livewire\Storefront\Account\Auth\Register;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    $this->seed();
    app()->instance('current_store', Store::query()->where('handle', 'acme-fashion')->firstOrFail());
});

test('guest customers are redirected to account login', function () {
    $this->get('http://shop.test/account')
        ->assertRedirect('http://shop.test/account/login');
});

test('customer can register and is authenticated with the customer guard', function () {
    Livewire::test(Register::class)
        ->set('name', 'New Customer')
        ->set('email', 'new@example.com')
        ->set('password', 'password')
        ->set('passwordConfirmation', 'password')
        ->set('marketingOptIn', true)
        ->call('register')
        ->assertHasNoErrors();

    $customer = Customer::withoutGlobalScopes()
        ->where('store_id', app('current_store')->id)
        ->where('email', 'new@example.com')
        ->firstOrFail();

    expect(Auth::guard('customer')->id())->toBe($customer->id)
        ->and($customer->marketing_opt_in)->toBeTrue();
});

test('customer can log in with seeded account credentials', function () {
    Livewire::test(Login::class)
        ->set('email', 'jane@example.com')
        ->set('password', 'password')
        ->call('login')
        ->assertHasNoErrors();

    expect(Auth::guard('customer')->check())->toBeTrue()
        ->and(Auth::guard('customer')->user()->email)->toBe('jane@example.com');
});

test('customer auth provider scopes credentials to current store', function () {
    $otherStore = Store::factory()->create();
    $otherCustomer = Customer::factory()->for($otherStore)->registered()->create([
        'email' => 'shared@example.com',
    ]);

    app()->instance('current_store', $otherStore);

    expect(Auth::guard('customer')->attempt([
        'email' => 'shared@example.com',
        'password' => 'password',
    ]))->toBeTrue()
        ->and(Auth::guard('customer')->id())->toBe($otherCustomer->id);

    Auth::guard('customer')->logout();
    app()->instance('current_store', Store::query()->where('handle', 'acme-fashion')->firstOrFail());

    expect(Auth::guard('customer')->attempt([
        'email' => 'shared@example.com',
        'password' => 'password',
    ]))->toBeFalse();
});

test('customer account pages show own orders and hide other customer orders', function () {
    $customer = Customer::withoutGlobalScopes()->where('email', 'jane@example.com')->firstOrFail();

    $this->actingAs($customer, 'customer')
        ->get('http://shop.test/account')
        ->assertOk()
        ->assertSee('#1001')
        ->assertSee('Order history');

    $this->actingAs($customer, 'customer')
        ->get('http://shop.test/account/orders')
        ->assertOk()
        ->assertSee('#1001')
        ->assertDontSee('#1002');

    $this->actingAs($customer, 'customer')
        ->get('http://shop.test/account/orders/1001')
        ->assertOk()
        ->assertSee('Order #1001')
        ->assertSee('Linen Shirt');

    $this->actingAs($customer, 'customer')
        ->get('http://shop.test/account/orders/1002')
        ->assertNotFound();
});

test('customer can manage address book records', function () {
    $customer = Customer::withoutGlobalScopes()->where('email', 'jane@example.com')->firstOrFail();
    $this->actingAs($customer, 'customer');

    Livewire::test(AddressIndex::class)
        ->call('startCreating')
        ->set('label', 'Work')
        ->set('address.first_name', 'Jane')
        ->set('address.last_name', 'Doe')
        ->set('address.address1', 'Office Street 2')
        ->set('address.city', 'Berlin')
        ->set('address.country_code', 'DE')
        ->set('address.postal_code', '10117')
        ->set('isDefault', true)
        ->call('save')
        ->assertHasNoErrors();

    $address = CustomerAddress::query()
        ->where('customer_id', $customer->id)
        ->where('label', 'Work')
        ->firstOrFail();

    expect($address->is_default)->toBeTrue()
        ->and(CustomerAddress::query()->where('customer_id', $customer->id)->where('is_default', true)->count())->toBe(1);

    Livewire::test(AddressIndex::class)
        ->call('deleteAddress', $address->id)
        ->assertHasNoErrors();

    expect(CustomerAddress::query()->whereKey($address->id)->exists())->toBeFalse();
});
