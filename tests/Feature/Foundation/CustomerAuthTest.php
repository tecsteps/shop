<?php

use App\Livewire\Storefront\Account\Auth\Login as CustomerLogin;
use App\Livewire\Storefront\Account\Auth\Register as CustomerRegister;
use App\Models\Customer;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('customer authentication is scoped to the current store', function () {
    $firstStore = Store::factory()->create();
    $secondStore = Store::factory()->create();

    Customer::factory()->create([
        'store_id' => $firstStore->getKey(),
        'email' => 'same@example.test',
        'password' => 'first-password',
    ]);

    Customer::factory()->create([
        'store_id' => $secondStore->getKey(),
        'email' => 'same@example.test',
        'password' => 'second-password',
    ]);

    app()->instance('current_store', $firstStore);

    expect(Auth::guard('customer')->attempt([
        'email' => 'same@example.test',
        'password' => 'first-password',
    ]))->toBeTrue();

    Auth::guard('customer')->logout();

    expect(Auth::guard('customer')->attempt([
        'email' => 'same@example.test',
        'password' => 'second-password',
    ]))->toBeFalse();

    app()->instance('current_store', $secondStore);

    expect(Auth::guard('customer')->attempt([
        'email' => 'same@example.test',
        'password' => 'second-password',
    ]))->toBeTrue();
});

test('customer login component authenticates within the resolved store', function () {
    $store = Store::factory()->create();
    $customer = Customer::factory()->create([
        'store_id' => $store->getKey(),
        'email' => 'customer@example.test',
        'password' => 'password',
    ]);

    app()->instance('current_store', $store);

    Livewire::test(CustomerLogin::class)
        ->set('email', $customer->email)
        ->set('password', 'password')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('account.dashboard', absolute: false));

    $this->assertAuthenticatedAs($customer, 'customer');
});

test('customer registration is unique per store', function () {
    $store = Store::factory()->create();

    app()->instance('current_store', $store);

    Livewire::test(CustomerRegister::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.test')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->set('marketing_opt_in', true)
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect(route('account.dashboard', absolute: false));

    expect(Customer::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('email', 'jane@example.test')
        ->exists())->toBeTrue();

    Livewire::test(CustomerRegister::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.test')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertHasErrors(['email']);
});
