<?php

use App\Enums\StoreDomainType;
use App\Livewire\Storefront\Account\Auth\Login;
use App\Models\Customer;
use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function seedLoginStore(): Store
{
    $store = Store::factory()->create(['name' => 'Shop']);
    StoreDomain::factory()->create([
        'store_id' => $store->getKey(),
        'hostname' => 'shop.test',
        'type' => StoreDomainType::Storefront->value,
        'is_primary' => 1,
    ]);

    return $store;
}

beforeEach(function () {
    RateLimiter::clear('customer-login:127.0.0.1:valid@example.com');
    RateLimiter::clear('customer-login:127.0.0.1:wrong@example.com');
});

it('signs in a customer with valid credentials', function () {
    $store = seedLoginStore();
    $customer = Customer::factory()->create([
        'store_id' => $store->getKey(),
        'email' => 'valid@example.com',
        'password_hash' => Hash::make('secret123'),
    ]);

    $this->get('http://shop.test/account/login');

    Livewire::test(Login::class)
        ->set('email', 'valid@example.com')
        ->set('password', 'secret123')
        ->call('authenticate')
        ->assertHasNoErrors();

    expect(Auth::guard('customer')->check())->toBeTrue()
        ->and(Auth::guard('customer')->id())->toBe($customer->getKey());
});

it('rejects invalid credentials', function () {
    $store = seedLoginStore();
    Customer::factory()->create([
        'store_id' => $store->getKey(),
        'email' => 'valid@example.com',
        'password_hash' => Hash::make('secret123'),
    ]);

    $this->get('http://shop.test/account/login');

    Livewire::test(Login::class)
        ->set('email', 'valid@example.com')
        ->set('password', 'wrong-pass')
        ->call('authenticate')
        ->assertHasErrors('email');

    expect(Auth::guard('customer')->check())->toBeFalse();
});

it('rate-limits after 5 failed attempts', function () {
    $store = seedLoginStore();
    Customer::factory()->create([
        'store_id' => $store->getKey(),
        'email' => 'wrong@example.com',
        'password_hash' => Hash::make('secret123'),
    ]);

    $this->get('http://shop.test/account/login');

    for ($i = 0; $i < 5; $i++) {
        Livewire::test(Login::class)
            ->set('email', 'wrong@example.com')
            ->set('password', 'wrong'.$i)
            ->call('authenticate')
            ->assertHasErrors('email');
    }

    $component = Livewire::test(Login::class)
        ->set('email', 'wrong@example.com')
        ->set('password', 'still-wrong')
        ->call('authenticate');

    $component->assertHasErrors('email');

    $messages = $component->errors()->get('email');
    expect(implode(' ', $messages))->toContain('Too many attempts');
});
