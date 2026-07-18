<?php

use App\Livewire\Storefront\Account\Auth\Login;
use App\Livewire\Storefront\Account\Auth\Register;
use App\Models\Customer;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('registers a new customer and logs them in', function () {
    $store = Store::factory()->create();
    app()->instance('current_store', $store);

    Livewire::test(Register::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect(route('storefront.account.dashboard'));

    $customer = Customer::query()->where('email', 'jane@example.com')->first();

    expect($customer)->not->toBeNull()
        ->and($customer->store_id)->toBe($store->id);

    expect(Auth::guard('customer')->check())->toBeTrue()
        ->and(Auth::guard('customer')->id())->toBe($customer->id);
});

it('rejects registration with a duplicate email for the same store', function () {
    $store = Store::factory()->create();
    app()->instance('current_store', $store);

    Customer::factory()->create(['store_id' => $store->id, 'email' => 'taken@example.com']);

    Livewire::test(Register::class)
        ->set('name', 'Someone Else')
        ->set('email', 'taken@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('register')
        ->assertHasErrors(['email']);
});

it('logs in an existing customer with valid credentials', function () {
    $store = Store::factory()->create();
    app()->instance('current_store', $store);

    $customer = Customer::factory()->create([
        'store_id' => $store->id,
        'email' => 'buyer@example.com',
        'password_hash' => bcrypt('secret123'),
    ]);

    Livewire::test(Login::class)
        ->set('email', 'buyer@example.com')
        ->set('password', 'secret123')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('storefront.account.dashboard'));

    expect(Auth::guard('customer')->id())->toBe($customer->id);
});

it('rejects login with invalid credentials', function () {
    $store = Store::factory()->create();
    app()->instance('current_store', $store);

    Customer::factory()->create([
        'store_id' => $store->id,
        'email' => 'buyer@example.com',
        'password_hash' => bcrypt('secret123'),
    ]);

    Livewire::test(Login::class)
        ->set('email', 'buyer@example.com')
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors(['email']);

    expect(Auth::guard('customer')->check())->toBeFalse();
});
