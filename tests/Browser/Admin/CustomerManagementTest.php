<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
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

function adminCustomerBrowserCreateOrder(Store $store, Customer $customer): Order
{
    $product = Product::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('handle', 'classic-cotton-t-shirt')
        ->firstOrFail();
    $variant = ProductVariant::withoutGlobalScopes()
        ->where('product_id', $product->getKey())
        ->firstOrFail();
    $shipping = 499;
    $total = $variant->price_amount + $shipping;

    $order = Order::factory()->create([
        'store_id' => $store->getKey(),
        'customer_id' => $customer->getKey(),
        'order_number' => '#1001',
        'payment_method' => PaymentMethod::CreditCard,
        'status' => OrderStatus::Paid,
        'financial_status' => FinancialStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Unfulfilled,
        'currency' => $store->default_currency,
        'subtotal_amount' => $variant->price_amount,
        'discount_amount' => 0,
        'shipping_amount' => $shipping,
        'tax_amount' => 0,
        'total_amount' => $total,
        'email' => $customer->email,
        'placed_at' => now()->subDay(),
    ]);

    OrderLine::factory()->create([
        'order_id' => $order->getKey(),
        'product_id' => $product->getKey(),
        'variant_id' => $variant->getKey(),
        'title_snapshot' => 'Classic Cotton T-Shirt',
        'sku_snapshot' => $variant->sku,
        'quantity' => 1,
        'unit_price_amount' => $variant->price_amount,
        'total_amount' => $variant->price_amount,
    ]);

    Payment::factory()->create([
        'order_id' => $order->getKey(),
        'method' => PaymentMethod::CreditCard,
        'status' => PaymentStatus::Captured,
        'amount' => $total,
        'currency' => $store->default_currency,
    ]);

    return $order->refresh();
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
    adminCustomerBrowserCreateOrder($store, $customer);

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
