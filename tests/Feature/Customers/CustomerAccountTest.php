<?php

use App\Models\Customer;
use App\Models\Store;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
});

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

it('creates a customer with hashed password', function (): void {
    $customer = Customer::factory()->for($this->store)->create([
        'email' => 'jane@example.com',
        'password_hash' => 'secret-password',
    ]);

    expect($customer->email)->toBe('jane@example.com')
        ->and(Hash::check('secret-password', $customer->password_hash))->toBeTrue();
});

it('enforces unique email per store but allows duplicates across stores', function (): void {
    Customer::factory()->for($this->store)->create(['email' => 'same@example.com']);

    $otherStore = Store::factory()->create();
    Customer::withoutGlobalScopes()->create([
        'store_id' => $otherStore->id,
        'email' => 'same@example.com',
        'name' => 'Dup',
    ]);

    expect(Customer::withoutGlobalScopes()->where('email', 'same@example.com')->count())->toBe(2);

    expect(fn () => Customer::factory()->for($this->store)->create(['email' => 'same@example.com']))
        ->toThrow(QueryException::class);
});

it('can authenticate using the hashed password column', function (): void {
    $customer = Customer::factory()->for($this->store)->create([
        'email' => 'auth@example.com',
        'password_hash' => 'password',
    ]);

    expect($customer->getAuthPassword())->toBe($customer->password_hash)
        ->and(Hash::check('password', $customer->getAuthPassword()))->toBeTrue();
});
