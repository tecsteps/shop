<?php

use App\Livewire\Storefront\Account\Auth\Login;
use App\Models\Customer;
use Livewire\Livewire;

beforeEach(function () {
    $this->ctx = createStoreContext();
});

it('authenticates a customer against the customer guard and redirects to account', function () {
    $customer = Customer::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'email' => 'customer@acme.test',
        'password' => bcrypt('password'),
    ]);

    Livewire::test(Login::class)
        ->set('email', 'customer@acme.test')
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect(route('storefront.account'));

    expect(auth()->guard('customer')->check())->toBeTrue()
        ->and(auth()->guard('customer')->id())->toBe($customer->id);
});

it('shows an error for invalid credentials', function () {
    Customer::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'email' => 'customer@acme.test',
        'password' => bcrypt('password'),
    ]);

    Livewire::test(Login::class)
        ->set('email', 'customer@acme.test')
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email')
        ->assertNoRedirect();
});
