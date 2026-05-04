<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\Store;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Browser\Playwright\Playwright;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Playwright::setHost('acme-fashion.test');

    $this->seed(DatabaseSeeder::class);
});

afterEach(function (): void {
    Playwright::setHost(null);
});

function tenantIsolationHost(): array
{
    return ['host' => 'acme-fashion.test'];
}

function tenantIsolationStore(string $handle): Store
{
    return Store::query()->where('handle', $handle)->firstOrFail();
}

function tenantIsolationFashionCustomer(): Customer
{
    $store = tenantIsolationStore('acme-fashion');

    return Customer::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('email', 'customer@acme.test')
        ->firstOrFail();
}

function tenantIsolationCreateOrders(): void
{
    $fashionStore = tenantIsolationStore('acme-fashion');
    $electronicsStore = tenantIsolationStore('acme-electronics');
    $fashionCustomer = tenantIsolationFashionCustomer();
    $electronicsCustomer = Customer::factory()->create([
        'store_id' => $electronicsStore->getKey(),
        'email' => 'customer@acme.test',
        'password' => 'password',
        'name' => 'Electronics Customer',
    ]);

    foreach (['#1001', '#1002', '#1004'] as $orderNumber) {
        Order::factory()
            ->forCustomer($fashionCustomer)
            ->paid()
            ->create([
                'store_id' => $fashionStore->getKey(),
                'customer_id' => $fashionCustomer->getKey(),
                'order_number' => $orderNumber,
                'email' => $fashionCustomer->email,
            ]);
    }

    Order::factory()
        ->forCustomer($electronicsCustomer)
        ->paid()
        ->create([
            'store_id' => $electronicsStore->getKey(),
            'customer_id' => $electronicsCustomer->getKey(),
            'order_number' => '#2001',
            'email' => $electronicsCustomer->email,
        ]);
}

function tenantIsolationAdminLogin(): mixed
{
    return visit('/admin/login', tenantIsolationHost())
        ->fill('input[type=email]', 'admin@acme.test')
        ->fill('input[type=password]', 'password')
        ->click('@admin-login-button')
        ->wait(1)
        ->assertPathIs('/admin')
        ->assertSee('Dashboard')
        ->assertNoJavaScriptErrors();
}

function tenantIsolationCustomerLogin(): mixed
{
    return visit('/account/login', tenantIsolationHost())
        ->fill('input[type=email]', 'customer@acme.test')
        ->fill('input[type=password]', 'password')
        ->click('@customer-login-button')
        ->wait(1)
        ->assertPathIs('/account')
        ->assertSee('My Account')
        ->assertSee('John Doe')
        ->assertNoJavaScriptErrors();
}

test('storefront only shows current store products', function (): void {
    visit('/', tenantIsolationHost())
        ->assertSee('Acme Fashion')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertDontSee('Pro Laptop 15')
        ->assertDontSee('Wireless Headphones')
        ->assertNoJavaScriptErrors();
});

test('storefront collections only contain current store products', function (): void {
    visit('/collections/t-shirts', tenantIsolationHost())
        ->assertSee('T-Shirts')
        ->assertSee('Classic Cotton T-Shirt')
        ->assertDontSee('Pro Laptop 15')
        ->assertDontSee('Wireless Headphones')
        ->assertNoJavaScriptErrors();
});

test('admin cannot see other store products or orders', function (): void {
    tenantIsolationCreateOrders();

    tenantIsolationAdminLogin()
        ->click('a[href$="/admin/products"]')
        ->wait(1)
        ->assertPathIs('/admin/products')
        ->assertSee('Products')
        ->assertDontSee('Pro Laptop 15')
        ->assertDontSee('Wireless Headphones')
        ->click('a[href$="/admin/orders"]')
        ->wait(1)
        ->assertPathIs('/admin/orders')
        ->assertSee('#1001')
        ->assertDontSee('#2001')
        ->assertNoJavaScriptErrors();
});

test('search only returns current store products', function (): void {
    visit('/search?q=cotton', tenantIsolationHost())
        ->assertSee('Classic Cotton T-Shirt')
        ->assertDontSee('Pro Laptop 15')
        ->assertNoJavaScriptErrors();

    visit('/search?q=laptop', tenantIsolationHost())
        ->assertDontSee('Pro Laptop 15')
        ->assertSee('No products found')
        ->assertNoJavaScriptErrors();
});

test('customer accounts are scoped to their store', function (): void {
    tenantIsolationCreateOrders();

    tenantIsolationCustomerLogin()
        ->click('nav[aria-label="Account navigation"] a[href$="/account/orders"]')
        ->wait(1)
        ->assertPathIs('/account/orders')
        ->assertSee('#1001')
        ->assertSee('#1002')
        ->assertSee('#1004')
        ->assertDontSee('#2001')
        ->assertNoJavaScriptErrors();
});
