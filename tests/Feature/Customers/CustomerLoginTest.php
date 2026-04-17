<?php

use App\Livewire\Storefront\Account\Auth\Login;
use App\Livewire\Storefront\Account\Auth\Register;
use App\Models\Customer;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
});

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

it('registers a new customer and logs them in', function (): void {
    Livewire::test(Register::class)
        ->set('name', 'Ada Lovelace')
        ->set('email', 'ada@example.com')
        ->set('password', 'secretpass')
        ->set('password_confirmation', 'secretpass')
        ->call('register')
        ->assertHasNoErrors();

    expect(Auth::guard('customer')->check())->toBeTrue();
    expect(Customer::query()->where('email', 'ada@example.com')->exists())->toBeTrue();
});

it('logs in an existing customer', function (): void {
    Customer::factory()->for($this->store)->create([
        'email' => 'grace@example.com',
        'password_hash' => 'hoppers',
    ]);

    Livewire::test(Login::class)
        ->set('email', 'grace@example.com')
        ->set('password', 'hoppers')
        ->call('login')
        ->assertHasNoErrors();

    expect(Auth::guard('customer')->check())->toBeTrue();
});

it('rejects invalid credentials', function (): void {
    Customer::factory()->for($this->store)->create([
        'email' => 'fail@example.com',
        'password_hash' => 'rightone',
    ]);

    Livewire::test(Login::class)
        ->set('email', 'fail@example.com')
        ->set('password', 'wrongone')
        ->call('login')
        ->assertHasErrors('email');

    expect(Auth::guard('customer')->check())->toBeFalse();
});

it('blocks unauthenticated access to account dashboard via route', function (): void {
    $this->get(route('storefront.account.dashboard'))
        ->assertRedirect(route('storefront.account.login'));
});

it('logs out a customer via the logout route', function (): void {
    $customer = Customer::factory()->for($this->store)->create([
        'password_hash' => 'password',
    ]);

    Auth::guard('customer')->login($customer);
    expect(Auth::guard('customer')->check())->toBeTrue();

    $this->post(route('storefront.account.logout'))
        ->assertRedirect(route('storefront.account.login'));

    expect(Auth::guard('customer')->check())->toBeFalse();
});
