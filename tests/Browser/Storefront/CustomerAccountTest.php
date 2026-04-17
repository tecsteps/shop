<?php

use App\Models\Customer;
use App\Models\Store;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
});

it('registers a new customer and lands on the account dashboard', function (): void {
    $page = visit('/account/register')
        ->assertSee('Create an account')
        ->fill('name', 'Test Buyer')
        ->fill('email', 'buyer@example.com')
        ->fill('password', 'secret-password')
        ->fill('password_confirmation', 'secret-password')
        ->click('Create account')
        ->wait(2);

    $page->assertPathIs('/account')
        ->assertNoJavaScriptErrors();

    expect(Customer::query()->where('email', 'buyer@example.com')->exists())->toBeTrue();
});

it('allows a customer to log in with valid credentials', function (): void {
    $store = Store::query()->first();

    Customer::query()->create([
        'store_id' => $store->getKey(),
        'email' => 'existing@example.com',
        'password_hash' => Hash::make('secret-password'),
        'name' => 'Existing Customer',
        'email_verified_at' => now(),
    ]);

    $page = visit('/account/login')
        ->fill('email', 'existing@example.com')
        ->fill('password', 'secret-password')
        ->click('Log in')
        ->wait(3);

    $page->assertPathIs('/account')
        ->assertNoJavaScriptErrors();
});
