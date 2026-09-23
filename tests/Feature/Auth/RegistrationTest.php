<?php

use App\Models\Customer;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('global administrator self-registration remains disabled', function () {
    $response = $this->get('/register');

    $response->assertNotFound();
});

test('customers register through the current store account flow', function () {
    $store = shopStore();
    app()->instance('current_store', $store);

    Livewire::test(\App\Livewire\Storefront\Account\Auth\Register::class)
        ->set('name', 'John Doe')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertRedirect(route('storefront.account.dashboard'));

    $customer = Customer::withoutGlobalScopes()->where('email', 'test@example.com')->firstOrFail();
    $this->assertAuthenticatedAs($customer, 'customer');
    expect($customer->store_id)->toBe($store->id)
        ->and(Hash::check('password', $customer->password))->toBeTrue();
});
