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

test('same email can register on different stores', function () {
    Customer::factory()->create([
        'store_id' => $this->store->id,
        'email' => 'shared@example.com',
    ]);

    $otherStore = Store::factory()->create();
    StoreDomain::factory()->create([
        'store_id' => $otherStore->id,
        'hostname' => 'other.test',
    ]);
    app()->instance('current_store', $otherStore);

    Livewire::test(Register::class)
        ->set('first_name', 'Alice')
        ->set('last_name', 'Smith')
        ->set('email', 'shared@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertRedirect(route('storefront.account.dashboard'));

    $otherStoreCustomer = Customer::where('email', 'shared@example.com')
        ->where('store_id', $otherStore->id)
        ->first();

    expect($otherStoreCustomer)->not->toBeNull()
        ->and(Customer::withoutGlobalScopes()->where('email', 'shared@example.com')->count())->toBe(2);
});

test('customer logout invalidates session', function () {
    $customer = Customer::factory()->create([
        'store_id' => $this->store->id,
    ]);

    $this->actingAs($customer, 'customer');
    expect(auth('customer')->check())->toBeTrue();

    $response = $this->post(route('storefront.account.logout'));

    $response->assertRedirect(route('storefront.account.login'));
    expect(auth('customer')->check())->toBeFalse();
});

test('guest cannot access dashboard', function () {
    $response = $this->get('http://shop.test/account');

    $response->assertRedirect(route('storefront.account.login'));
});

test('guest cannot access orders page', function () {
    $response = $this->get('http://shop.test/account/orders');

    $response->assertRedirect(route('storefront.account.login'));
});

test('guest cannot access addresses page', function () {
    $response = $this->get('http://shop.test/account/addresses');

    $response->assertRedirect(route('storefront.account.login'));
});

test('authenticated customer can access dashboard', function () {
    $customer = Customer::factory()->create([
        'store_id' => $this->store->id,
    ]);

    $response = $this->actingAs($customer, 'customer')
        ->get('http://shop.test/account');

    $response->assertOk();
});

test('login updates last_login_at timestamp', function () {
    $customer = Customer::factory()->create([
        'store_id' => $this->store->id,
        'email' => 'customer@example.com',
        'password' => bcrypt('password'),
        'last_login_at' => null,
    ]);

    Livewire::test(Login::class)
        ->set('email', 'customer@example.com')
        ->set('password', 'password')
        ->call('login');

    expect($customer->fresh()->last_login_at)->not->toBeNull();
});
