<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Store;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Pest\Browser\Playwright\Playwright;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Playwright::setHost('shop.test');

    $this->seed(DatabaseSeeder::class);
});

afterEach(function (): void {
    Playwright::setHost(null);
});

function storefrontAccountHost(): array
{
    return ['host' => 'shop.test'];
}

function storefrontAccountLogin(): mixed
{
    return visit('/account/login', storefrontAccountHost())
        ->fill('input[type=email]', 'customer@acme.test')
        ->fill('input[type=password]', 'password')
        ->click('@customer-login-button')
        ->wait(1)
        ->assertPathIs('/account')
        ->assertSee('My Account')
        ->assertSee('John Doe')
        ->assertNoJavaScriptErrors();
}

function storefrontAccountStore(): Store
{
    return Store::query()->where('handle', 'acme-fashion')->firstOrFail();
}

function storefrontAccountCustomer(): Customer
{
    $store = storefrontAccountStore();

    return Customer::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('email', 'customer@acme.test')
        ->firstOrFail();
}

/**
 * @return Collection<int, Order>
 */
function storefrontAccountCreateOrders(): Collection
{
    $store = storefrontAccountStore();
    $customer = storefrontAccountCustomer();

    return collect(['#1001', '#1002', '#1004'])
        ->map(function (string $orderNumber, int $index) use ($store, $customer): Order {
            $order = Order::factory()
                ->forCustomer($customer)
                ->paid()
                ->create([
                    'store_id' => $store->getKey(),
                    'customer_id' => $customer->getKey(),
                    'order_number' => $orderNumber,
                    'email' => $customer->email,
                    'subtotal_amount' => 2499,
                    'shipping_amount' => 499,
                    'tax_amount' => 0,
                    'total_amount' => 2998,
                    'placed_at' => now()->subDays($index),
                ]);

            OrderLine::factory()->create([
                'order_id' => $order->getKey(),
                'product_id' => null,
                'variant_id' => null,
                'title_snapshot' => 'Classic Cotton T-Shirt',
                'quantity' => 1,
                'unit_price_amount' => 2499,
                'total_amount' => 2499,
            ]);

            return $order;
        });
}

test('can register a new customer', function (): void {
    visit('/account/register', storefrontAccountHost())
        ->fill('input[wire\\:model="name"]', 'New Customer')
        ->fill('input[wire\\:model="email"]', 'new-customer-e2e@example.com')
        ->fill('input[wire\\:model="password"]', 'password123')
        ->fill('input[wire\\:model="password_confirmation"]', 'password123')
        ->click('@customer-register-button')
        ->wait(1)
        ->assertPathIs('/account')
        ->assertSee('My Account')
        ->assertNoJavaScriptErrors();
});

test('shows validation errors for duplicate email registration', function (): void {
    visit('/account/register', storefrontAccountHost())
        ->fill('input[wire\\:model="name"]', 'Duplicate Customer')
        ->fill('input[wire\\:model="email"]', 'customer@acme.test')
        ->fill('input[wire\\:model="password"]', 'password123')
        ->fill('input[wire\\:model="password_confirmation"]', 'password123')
        ->click('@customer-register-button')
        ->wait(1)
        ->assertPathIs('/account/register')
        ->assertSee('already been taken')
        ->assertNoJavaScriptErrors();
});

test('shows validation errors for mismatched passwords', function (): void {
    visit('/account/register', storefrontAccountHost())
        ->fill('input[wire\\:model="name"]', 'Test Customer')
        ->fill('input[wire\\:model="email"]', 'mismatch@example.com')
        ->fill('input[wire\\:model="password"]', 'password123')
        ->fill('input[wire\\:model="password_confirmation"]', 'different456')
        ->click('@customer-register-button')
        ->wait(1)
        ->assertPathIs('/account/register')
        ->assertSee('password')
        ->assertNoJavaScriptErrors();
});

test('can log in as existing customer', function (): void {
    storefrontAccountLogin();
});

test('shows error for invalid customer credentials', function (): void {
    visit('/account/login', storefrontAccountHost())
        ->fill('input[type=email]', 'customer@acme.test')
        ->fill('input[type=password]', 'wrongpassword')
        ->click('@customer-login-button')
        ->wait(1)
        ->assertPathIs('/account/login')
        ->assertSee('Invalid credentials')
        ->assertNoJavaScriptErrors();
});

test('redirects unauthenticated customers to login', function (): void {
    visit('/account', storefrontAccountHost())
        ->wait(1)
        ->assertPathIs('/account/login')
        ->assertSee('Log in')
        ->assertNoJavaScriptErrors();
});

test('shows order history for logged in customer', function (): void {
    storefrontAccountCreateOrders();

    storefrontAccountLogin()
        ->click('nav[aria-label="Account navigation"] a[href$="/account/orders"]')
        ->wait(1)
        ->assertPathIs('/account/orders')
        ->assertSee('#1001')
        ->assertSee('#1002')
        ->assertSee('#1004')
        ->assertNoJavaScriptErrors();
});

test('shows order detail for customer order', function (): void {
    $orders = storefrontAccountCreateOrders();
    $order = $orders->first();

    storefrontAccountLogin()
        ->click('nav[aria-label="Account navigation"] a[href$="/account/orders"]')
        ->wait(1)
        ->click('a[href$="/account/orders/'.$order->getKey().'"]')
        ->wait(1)
        ->assertPathIs('/account/orders/'.$order->getKey())
        ->assertSee('#1001')
        ->assertSee('Subtotal')
        ->assertSee('Total')
        ->assertNoJavaScriptErrors();
});

test('can view addresses', function (): void {
    storefrontAccountLogin()
        ->click('nav[aria-label="Account navigation"] a[href$="/account/addresses"]')
        ->wait(1)
        ->assertPathIs('/account/addresses')
        ->assertSee('Main Street 1')
        ->assertSee('Berlin')
        ->assertNoJavaScriptErrors();
});

test('can add a new address', function (): void {
    storefrontAccountLogin()
        ->click('nav[aria-label="Account navigation"] a[href$="/account/addresses"]')
        ->wait(1)
        ->click('button:has-text("Add address")')
        ->wait(1)
        ->fill('input[wire\\:model="address.first_name"]', 'John')
        ->fill('input[wire\\:model="address.last_name"]', 'Doe')
        ->fill('input[wire\\:model="address.address1"]', 'New Street 42')
        ->fill('input[wire\\:model="address.city"]', 'Hamburg')
        ->fill('input[wire\\:model="address.postal_code"]', '20095')
        ->select('select[wire\\:model="address.country"]', 'DE')
        ->click('form[wire\\:submit="saveAddress"] button[type="submit"]')
        ->wait(1)
        ->assertSee('Address saved')
        ->assertSee('New Street 42')
        ->assertSee('Hamburg')
        ->assertNoJavaScriptErrors();
});

test('can edit an existing address', function (): void {
    storefrontAccountLogin()
        ->click('nav[aria-label="Account navigation"] a[href$="/account/addresses"]')
        ->wait(1)
        ->click('article:has-text("Main Street 1") button:has-text("Edit")')
        ->wait(1)
        ->fill('input[wire\\:model="address.city"]', 'Frankfurt')
        ->click('form[wire\\:submit="saveAddress"] button[type="submit"]')
        ->wait(1)
        ->assertSee('Address saved')
        ->assertSee('Frankfurt')
        ->assertNoJavaScriptErrors();
});

test('can log out', function (): void {
    storefrontAccountLogin()
        ->click('@customer-logout-button')
        ->wait(1)
        ->assertPathIs('/account/login')
        ->assertSee('Log in')
        ->assertNoJavaScriptErrors();
});
