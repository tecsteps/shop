<?php

use App\Models\Customer;
use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

beforeEach(function () {
    $this->store = Store::factory()->create();
    StoreDomain::factory()->create([
        'store_id' => $this->store->id,
        'hostname' => 'test-store.test',
        'type' => 'storefront',
    ]);
    app()->instance('current_store', $this->store);
});

it('customer guard uses session driver with customers provider', function () {
    expect(config('auth.guards.customer.driver'))->toBe('session');
    expect(config('auth.guards.customer.provider'))->toBe('customers');
});

it('CustomerUserProvider scopes credential queries by store_id', function () {
    $storeB = Store::factory()->create();

    Customer::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'email' => 'customer@example.com',
        'password' => bcrypt('secret123'),
        'name' => 'Customer A',
    ]);

    Customer::withoutGlobalScopes()->create([
        'store_id' => $storeB->id,
        'email' => 'customer@example.com',
        'password' => bcrypt('different'),
        'name' => 'Customer B',
    ]);

    $result = Auth::guard('customer')->attempt([
        'email' => 'customer@example.com',
        'password' => 'secret123',
    ]);

    expect($result)->toBeTrue();
    $authedCustomer = Auth::guard('customer')->user();
    expect($authedCustomer->store_id)->toBe($this->store->id);
});

it('customer login with valid credentials succeeds', function () {
    Customer::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'email' => 'buyer@example.com',
        'password' => bcrypt('secret123'),
        'name' => 'Buyer',
    ]);

    Livewire::test(\App\Livewire\Storefront\Account\Auth\Login::class)
        ->set('email', 'buyer@example.com')
        ->set('password', 'secret123')
        ->call('login')
        ->assertRedirect('/account');

    expect(Auth::guard('customer')->check())->toBeTrue();
});

it('customer login with invalid credentials fails', function () {
    Customer::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'email' => 'buyer@example.com',
        'password' => bcrypt('secret123'),
        'name' => 'Buyer',
    ]);

    Livewire::test(\App\Livewire\Storefront\Account\Auth\Login::class)
        ->set('email', 'buyer@example.com')
        ->set('password', 'wrong')
        ->call('login')
        ->assertHasErrors('email');

    expect(Auth::guard('customer')->check())->toBeFalse();
});

it('customer login is rate-limited to 5 attempts per minute', function () {
    Customer::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'email' => 'buyer@example.com',
        'password' => bcrypt('secret123'),
        'name' => 'Buyer',
    ]);

    for ($i = 0; $i < 5; $i++) {
        Livewire::test(\App\Livewire\Storefront\Account\Auth\Login::class)
            ->set('email', 'buyer@example.com')
            ->set('password', 'wrong')
            ->call('login');
    }

    Livewire::test(\App\Livewire\Storefront\Account\Auth\Login::class)
        ->set('email', 'buyer@example.com')
        ->set('password', 'wrong')
        ->call('login')
        ->assertHasErrors('email');
});

it('customer registration with valid data succeeds', function () {
    Livewire::test(\App\Livewire\Storefront\Account\Auth\Register::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('password', 'securepass1')
        ->set('password_confirmation', 'securepass1')
        ->call('register')
        ->assertRedirect('/account');

    expect(Auth::guard('customer')->check())->toBeTrue();

    $customer = Customer::withoutGlobalScopes()->where('email', 'jane@example.com')->first();
    expect($customer)->not->toBeNull();
    expect($customer->store_id)->toBe($this->store->id);
});

it('customer registration requires name, email, password, and password_confirmation', function () {
    Livewire::test(\App\Livewire\Storefront\Account\Auth\Register::class)
        ->set('name', '')
        ->set('email', '')
        ->set('password', '')
        ->call('register')
        ->assertHasErrors(['name', 'email', 'password']);
});

it('customer registration enforces minimum password length of 8', function () {
    Livewire::test(\App\Livewire\Storefront\Account\Auth\Register::class)
        ->set('name', 'Jane')
        ->set('email', 'jane@example.com')
        ->set('password', 'short')
        ->set('password_confirmation', 'short')
        ->call('register')
        ->assertHasErrors('password');
});

it('customer registration enforces password confirmation match', function () {
    Livewire::test(\App\Livewire\Storefront\Account\Auth\Register::class)
        ->set('name', 'Jane')
        ->set('email', 'jane@example.com')
        ->set('password', 'securepass1')
        ->set('password_confirmation', 'different')
        ->call('register')
        ->assertHasErrors('password');
});

it('customer email must be unique per store', function () {
    Customer::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'email' => 'existing@example.com',
        'name' => 'Existing',
        'password' => bcrypt('password'),
    ]);

    Livewire::test(\App\Livewire\Storefront\Account\Auth\Register::class)
        ->set('name', 'Jane')
        ->set('email', 'existing@example.com')
        ->set('password', 'securepass1')
        ->set('password_confirmation', 'securepass1')
        ->call('register')
        ->assertHasErrors('email');
});

it('same email can register in different stores', function () {
    $storeB = Store::factory()->create();

    Customer::withoutGlobalScopes()->create([
        'store_id' => $storeB->id,
        'email' => 'shared@example.com',
        'name' => 'Shared',
        'password' => bcrypt('password'),
    ]);

    Livewire::test(\App\Livewire\Storefront\Account\Auth\Register::class)
        ->set('name', 'Jane')
        ->set('email', 'shared@example.com')
        ->set('password', 'securepass1')
        ->set('password_confirmation', 'securepass1')
        ->call('register')
        ->assertRedirect('/account');

    $count = Customer::withoutGlobalScopes()->where('email', 'shared@example.com')->count();
    expect($count)->toBe(2);
});

it('customer registration supports optional marketing_opt_in', function () {
    Livewire::test(\App\Livewire\Storefront\Account\Auth\Register::class)
        ->set('name', 'Jane')
        ->set('email', 'jane@example.com')
        ->set('password', 'securepass1')
        ->set('password_confirmation', 'securepass1')
        ->set('marketing_opt_in', true)
        ->call('register')
        ->assertRedirect('/account');

    $customer = Customer::withoutGlobalScopes()->where('email', 'jane@example.com')->first();
    expect($customer->marketing_opt_in)->toBeTrue();
});

it('customer registration defaults marketing_opt_in to false', function () {
    Livewire::test(\App\Livewire\Storefront\Account\Auth\Register::class)
        ->set('name', 'Jane')
        ->set('email', 'jane@example.com')
        ->set('password', 'securepass1')
        ->set('password_confirmation', 'securepass1')
        ->call('register')
        ->assertRedirect('/account');

    $customer = Customer::withoutGlobalScopes()->where('email', 'jane@example.com')->first();
    expect($customer->marketing_opt_in)->toBeFalse();
});
