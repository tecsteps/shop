<?php

use App\Livewire\Storefront\Account\Auth\Login as CustomerLoginComponent;
use App\Livewire\Storefront\Account\Auth\Register as CustomerRegisterComponent;
use App\Models\Customer;
use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    StoreDomain::factory()->create([
        'store_id' => $this->store->id,
        'hostname' => 'shop.test',
        'type' => 'storefront',
        'is_primary' => true,
    ]);
    app()->instance('current_store', $this->store);
});

test('renders the customer login page', function () {
    $response = $this->withHeader('Host', 'shop.test')
        ->get('/account/login');

    $response->assertStatus(200);
    $response->assertSee('Customer Login');
});

test('authenticates a customer with valid credentials', function () {
    $customer = Customer::factory()->create([
        'store_id' => $this->store->id,
        'email' => 'customer@example.com',
        'password' => bcrypt('password123'),
    ]);

    Livewire::test(CustomerLoginComponent::class)
        ->set('email', 'customer@example.com')
        ->set('password', 'password123')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('customer.dashboard'));

    expect(Auth::guard('customer')->check())->toBeTrue();
    expect(Auth::guard('customer')->id())->toBe($customer->id);
});

test('rejects invalid customer credentials', function () {
    Customer::factory()->create([
        'store_id' => $this->store->id,
        'email' => 'customer@example.com',
        'password' => bcrypt('password123'),
    ]);

    Livewire::test(CustomerLoginComponent::class)
        ->set('email', 'customer@example.com')
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email');

    expect(Auth::guard('customer')->check())->toBeFalse();
});

test('scopes customer login to the current store', function () {
    $otherStore = Store::factory()->create();
    Customer::factory()->create([
        'store_id' => $otherStore->id,
        'email' => 'customer@example.com',
        'password' => bcrypt('password123'),
    ]);

    Livewire::test(CustomerLoginComponent::class)
        ->set('email', 'customer@example.com')
        ->set('password', 'password123')
        ->call('login')
        ->assertHasErrors('email');

    expect(Auth::guard('customer')->check())->toBeFalse();
});

test('rate limits customer login attempts', function () {
    Customer::factory()->create([
        'store_id' => $this->store->id,
        'email' => 'customer@example.com',
    ]);

    for ($i = 0; $i < 5; $i++) {
        Livewire::test(CustomerLoginComponent::class)
            ->set('email', 'customer@example.com')
            ->set('password', 'wrong-password')
            ->call('login');
    }

    Livewire::test(CustomerLoginComponent::class)
        ->set('email', 'customer@example.com')
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email');
});

test('registers a new customer', function () {
    Livewire::test(CustomerRegisterComponent::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->set('marketing_opt_in', true)
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect(route('customer.dashboard'));

    expect(Auth::guard('customer')->check())->toBeTrue();

    $customer = Customer::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('email', 'jane@example.com')
        ->first();

    expect($customer)->not->toBeNull();
    expect($customer->name)->toBe('Jane Doe');
    expect($customer->marketing_opt_in)->toBeTrue();
});

test('rejects duplicate email registration in the same store', function () {
    Customer::factory()->create([
        'store_id' => $this->store->id,
        'email' => 'existing@example.com',
    ]);

    Livewire::test(CustomerRegisterComponent::class)
        ->set('name', 'Another User')
        ->set('email', 'existing@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('register')
        ->assertHasErrors('email');
});

test('allows same email in different stores', function () {
    $otherStore = Store::factory()->create();
    Customer::factory()->create([
        'store_id' => $otherStore->id,
        'email' => 'shared@example.com',
    ]);

    Livewire::test(CustomerRegisterComponent::class)
        ->set('name', 'New Customer')
        ->set('email', 'shared@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect(route('customer.dashboard'));

    expect(Auth::guard('customer')->check())->toBeTrue();

    $count = Customer::withoutGlobalScopes()
        ->where('email', 'shared@example.com')
        ->count();

    expect($count)->toBe(2);
});

test('logs out customer and redirects to login', function () {
    $customer = Customer::factory()->create([
        'store_id' => $this->store->id,
    ]);

    $response = $this->withHeader('Host', 'shop.test')
        ->actingAs($customer, 'customer')
        ->post('/account/logout');

    $response->assertRedirect(route('customer.login'));
    expect(Auth::guard('customer')->check())->toBeFalse();
});

test('renders the customer register page', function () {
    $response = $this->withHeader('Host', 'shop.test')
        ->get('/account/register');

    $response->assertStatus(200);
    $response->assertSee('Create an account');
});

test('requires authentication for account dashboard', function () {
    $response = $this->withHeader('Host', 'shop.test')
        ->get('/account');

    $response->assertRedirect();
});
