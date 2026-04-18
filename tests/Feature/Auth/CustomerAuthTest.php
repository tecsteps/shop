<?php

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

it('renders the customer login page', function (): void {
    $this->createStoreContext(['hostname' => 'customer-login.test']);

    $this->get('http://customer-login.test/account/login')
        ->assertOk()
        ->assertSee('Sign in');
});

it('registers a new customer and logs them in', function (): void {
    $this->createStoreContext(['hostname' => 'customer-reg.test']);

    \Livewire\Livewire::test(\App\Livewire\Storefront\Account\Auth\Register::class)
        ->set('first_name', 'Billy')
        ->set('last_name', 'Buyer')
        ->set('email', 'billy@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertRedirect(route('account.dashboard'));

    expect(Auth::guard('customer')->check())->toBeTrue();
    expect(Customer::withoutGlobalScopes()->where('email', 'billy@example.com')->exists())->toBeTrue();
});

it('authenticates a customer with valid credentials', function (): void {
    $ctx = $this->createStoreContext(['hostname' => 'customer-a.test']);

    Customer::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'email' => 'c@example.com',
        'password' => Hash::make('password'),
        'first_name' => 'C',
        'last_name' => 'User',
        'state' => 'active',
        'email_verified_at' => now(),
    ]);

    \Livewire\Livewire::test(\App\Livewire\Storefront\Account\Auth\Login::class)
        ->set('email', 'c@example.com')
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect(route('account.dashboard'));

    expect(Auth::guard('customer')->check())->toBeTrue();
});

it('scopes customer login to current store', function (): void {
    $a = $this->createStoreContext(['hostname' => 'store-a.test']);

    Customer::withoutGlobalScopes()->create([
        'store_id' => $a['store']->id,
        'email' => 'only-in-a@example.com',
        'password' => Hash::make('password'),
        'state' => 'active',
    ]);

    // Switch to store B
    $b = $this->createStoreContext(['hostname' => 'store-b.test']);

    \Livewire\Livewire::test(\App\Livewire\Storefront\Account\Auth\Login::class)
        ->set('email', 'only-in-a@example.com')
        ->set('password', 'password')
        ->call('login')
        ->assertHasErrors(['email']);

    expect(Auth::guard('customer')->check())->toBeFalse();
});

it('allows the same email in different stores', function (): void {
    $a = $this->createStoreContext(['hostname' => 'both-a.test']);
    Customer::withoutGlobalScopes()->create([
        'store_id' => $a['store']->id,
        'email' => 'shared@example.com',
        'password' => Hash::make('password'),
        'state' => 'active',
    ]);

    $b = $this->createStoreContext(['hostname' => 'both-b.test']);

    \Livewire\Livewire::test(\App\Livewire\Storefront\Account\Auth\Register::class)
        ->set('first_name', 'S')
        ->set('last_name', 'B')
        ->set('email', 'shared@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertRedirect(route('account.dashboard'));

    expect(Customer::withoutGlobalScopes()->where('email', 'shared@example.com')->count())->toBe(2);
});
