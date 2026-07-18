<?php

use App\Enums\StoreDomainType;
use App\Enums\StoreStatus;
use App\Enums\StoreUserRole;
use App\Enums\UserStatus;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreSettings;
use App\Models\StoreUser;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('persists foundation factories with relationships and enum casts', function () {
    $organization = Organization::factory()->create();
    $store = Store::factory()->for($organization)->create();
    $domain = StoreDomain::factory()->for($store)->create();
    $settings = StoreSettings::factory()->for($store)->create();
    $user = User::factory()->create();
    $user->stores()->attach($store, ['role' => StoreUserRole::Owner]);

    expect($store->organization->is($organization))->toBeTrue()
        ->and($organization->stores)->toHaveCount(1)
        ->and($store->status)->toBe(StoreStatus::Active)
        ->and($domain->type)->toBe(StoreDomainType::Storefront)
        ->and($settings->settings_json)->toBeArray()
        ->and($user->roleForStore($store))->toBe(StoreUserRole::Owner)
        ->and($user->status)->toBe(UserStatus::Active)
        ->and(StoreUser::query()->firstOrFail()->created_at)->not->toBeNull();
});

it('persists customer factories with the specified password hash column', function () {
    $store = Store::factory()->create();
    $customer = Customer::factory()->for($store)->create();
    $address = CustomerAddress::factory()->for($customer)->create();

    expect(Hash::check('password', $customer->password_hash))->toBeTrue()
        ->and($customer->getAuthPasswordName())->toBe('password_hash')
        ->and($customer->addresses->first()->is($address))->toBeTrue()
        ->and($address->address_json)->toBeArray();
});

it('configures the customer guard provider and password broker', function () {
    expect(config('auth.guards.customer.provider'))->toBe('customers')
        ->and(config('auth.providers.customers.model'))->toBe(Customer::class)
        ->and(config('auth.passwords.customers.table'))->toBe('customer_password_reset_tokens');
});

it('enforces status check constraints in sqlite', function () {
    expect(fn () => DB::table((new User)->getTable())->insert([
        'name' => 'Invalid User',
        'email' => 'invalid@example.com',
        'status' => 'invalid',
        'password' => Hash::make('password'),
        'created_at' => now(),
        'updated_at' => now(),
    ]))
        ->toThrow(QueryException::class);
});
