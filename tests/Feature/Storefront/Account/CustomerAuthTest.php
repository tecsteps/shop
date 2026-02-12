<?php

use App\Livewire\Storefront\Account\Auth\Login;
use App\Livewire\Storefront\Account\Auth\Register;
use App\Models\Customer;
use App\Models\Store;
use App\Models\StoreDomain;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    StoreDomain::factory()->create([
        'store_id' => $this->store->id,
        'hostname' => 'shop.test',
    ]);
    app()->instance('current_store', $this->store);
});

test('customer login page renders', function () {
    $response = $this->get('http://shop.test/account/login');

    $response->assertOk();
    $response->assertSeeLivewire(Login::class);
});

test('customer can log in with valid credentials', function () {
    $customer = Customer::factory()->create([
        'store_id' => $this->store->id,
        'email' => 'customer@example.com',
        'password' => bcrypt('password'),
    ]);

    Livewire::test(Login::class)
        ->set('email', 'customer@example.com')
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect(route('storefront.account.dashboard'));

    expect(auth('customer')->check())->toBeTrue()
        ->and(auth('customer')->id())->toBe($customer->id);
});

test('customer cannot log in with wrong password', function () {
    Customer::factory()->create([
        'store_id' => $this->store->id,
        'email' => 'customer@example.com',
        'password' => bcrypt('password'),
    ]);

    Livewire::test(Login::class)
        ->set('email', 'customer@example.com')
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email');

    expect(auth('customer')->check())->toBeFalse();
});

test('customer login validates required fields', function () {
    Livewire::test(Login::class)
        ->set('email', '')
        ->set('password', '')
        ->call('login')
        ->assertHasErrors(['email', 'password']);
});

test('customer register page renders', function () {
    $response = $this->get('http://shop.test/account/register');

    $response->assertOk();
    $response->assertSeeLivewire(Register::class);
});

test('customer can register with valid data', function () {
    Livewire::test(Register::class)
        ->set('first_name', 'Jane')
        ->set('last_name', 'Doe')
        ->set('email', 'jane@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertRedirect(route('storefront.account.dashboard'));

    expect(auth('customer')->check())->toBeTrue();

    $customer = Customer::where('email', 'jane@example.com')
        ->where('store_id', $this->store->id)
        ->first();
    expect($customer)->not->toBeNull()
        ->and($customer->first_name)->toBe('Jane')
        ->and($customer->last_name)->toBe('Doe');
});

test('customer cannot register with existing email', function () {
    Customer::factory()->create([
        'store_id' => $this->store->id,
        'email' => 'existing@example.com',
    ]);

    Livewire::test(Register::class)
        ->set('first_name', 'Jane')
        ->set('last_name', 'Doe')
        ->set('email', 'existing@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertHasErrors('email');
});

test('customer register validates required fields', function () {
    Livewire::test(Register::class)
        ->set('first_name', '')
        ->set('last_name', '')
        ->set('email', '')
        ->set('password', '')
        ->set('password_confirmation', '')
        ->call('register')
        ->assertHasErrors(['first_name', 'last_name', 'email', 'password']);
});

test('customer register validates password confirmation', function () {
    Livewire::test(Register::class)
        ->set('first_name', 'Jane')
        ->set('last_name', 'Doe')
        ->set('email', 'jane@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'different')
        ->call('register')
        ->assertHasErrors('password');
});
