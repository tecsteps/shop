<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Browser\Playwright\Playwright;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Playwright::setHost('shop.test');

    $this->seed(DatabaseSeeder::class);
});

afterEach(function (): void {
    Playwright::setHost(null);
});

function adminCustomerBrowserHost(): array
{
    return ['host' => 'shop.test'];
}

function adminCustomerBrowserAuthenticate(mixed $testCase): Store
{
    $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $user = User::query()->where('email', 'admin@acme.test')->firstOrFail();

    $testCase->actingAs($user);
    $testCase->withSession(['current_store_id' => $store->getKey()]);

    return $store;
}

function adminCustomerBrowserCustomer(Store $store): Customer
{
    return Customer::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('email', 'customer@acme.test')
        ->firstOrFail();
}

function adminCustomerBrowserOrder(Store $store, Customer $customer): Order
{
    return Order::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('customer_id', $customer->getKey())
        ->where('order_number', '#1001')
        ->firstOrFail();
}

function adminCustomerBrowserOpenCustomers(mixed $testCase): mixed
{
    adminCustomerBrowserAuthenticate($testCase);

    return visit('/admin/customers', adminCustomerBrowserHost())
        ->wait(1)
        ->assertPathIs('/admin/customers')
        ->assertSee('Customers')
        ->assertNoJavaScriptErrors();
}

function adminCustomerBrowserOpenCustomer(mixed $testCase): mixed
{
    return adminCustomerBrowserOpenCustomers($testCase)
        ->assertSee('John Doe')
        ->click('a:has-text("John Doe")')
        ->wait(1)
        ->assertPathContains('/admin/customers/')
        ->assertNoJavaScriptErrors();
}

test('shows the customer list', function (): void {
    adminCustomerBrowserOpenCustomers($this)
        ->assertSee('customer@acme.test')
        ->assertSee('John Doe')
        ->assertNoJavaScriptErrors();
});

test('shows customer detail with order history', function (): void {
    $store = adminCustomerBrowserAuthenticate($this);
    $customer = adminCustomerBrowserCustomer($store);
    adminCustomerBrowserOrder($store, $customer);

    visit('/admin/customers', adminCustomerBrowserHost())
        ->wait(1)
        ->assertPathIs('/admin/customers')
        ->click('a:has-text("John Doe")')
        ->wait(1)
        ->assertPathContains('/admin/customers/')
        ->assertSee('John Doe')
        ->assertSee('customer@acme.test')
        ->assertSee('Order history')
        ->assertSee('#1001')
        ->assertNoJavaScriptErrors();
});

test('shows customer addresses', function (): void {
    adminCustomerBrowserOpenCustomer($this)
        ->assertSee('John Doe')
        ->assertSee('Addresses')
        ->assertSee('Home')
        ->assertNoJavaScriptErrors();
});
