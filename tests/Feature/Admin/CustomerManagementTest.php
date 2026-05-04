<?php

use App\Livewire\Admin\Customers\Index as AdminCustomersIndex;
use App\Livewire\Admin\Customers\Show as AdminCustomerShow;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function adminCustomerManagementStore(): Store
{
    $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    app()->instance('current_store', $store);

    return $store;
}

function adminCustomerManagementUser(): User
{
    return User::query()->where('email', 'admin@acme.test')->firstOrFail();
}

function adminCustomerManagementCustomer(Store $store, string $email, string $name): Customer
{
    return Customer::factory()->create([
        'store_id' => $store->getKey(),
        'email' => $email,
        'name' => $name,
    ]);
}

function adminCustomerManagementOrder(Store $store, Customer $customer, string $orderNumber, int $totalAmount): Order
{
    return Order::factory()->paid()->create([
        'store_id' => $store->getKey(),
        'customer_id' => $customer->getKey(),
        'order_number' => $orderNumber,
        'subtotal_amount' => $totalAmount,
        'shipping_amount' => 0,
        'tax_amount' => 0,
        'total_amount' => $totalAmount,
        'email' => $customer->email,
    ]);
}

test('admin customer routes require authentication and render store scoped customers', function (): void {
    $store = adminCustomerManagementStore();
    $customer = adminCustomerManagementCustomer($store, 'jane@example.test', 'Jane Example');
    adminCustomerManagementOrder($store, $customer, '#8101', 7000);
    CustomerAddress::factory()->default()->create([
        'customer_id' => $customer->getKey(),
        'label' => 'Home',
    ]);

    $otherStore = Store::factory()->create();
    $otherCustomer = adminCustomerManagementCustomer($otherStore, 'other@example.test', 'Other Customer');

    $this->get('/admin/customers')->assertRedirect('/admin/login');

    $this->actingAs(adminCustomerManagementUser())
        ->withSession(['current_store_id' => $store->getKey()])
        ->get('/admin/customers')
        ->assertSuccessful()
        ->assertSee('Jane Example')
        ->assertSee('70.00 EUR')
        ->assertDontSee('Other Customer');

    $this->actingAs(adminCustomerManagementUser())
        ->withSession(['current_store_id' => $store->getKey()])
        ->get('/admin/customers/'.$customer->getKey())
        ->assertSuccessful()
        ->assertSee('Jane Example')
        ->assertSee('#8101')
        ->assertSee('Home');

    $this->actingAs(adminCustomerManagementUser())
        ->withSession(['current_store_id' => $store->getKey()])
        ->get('/admin/customers/'.$otherCustomer->getKey())
        ->assertNotFound();
});

test('admin customer index filters by name and email', function (): void {
    $store = adminCustomerManagementStore();
    $user = adminCustomerManagementUser();
    adminCustomerManagementCustomer($store, 'jane@example.test', 'Jane Example');
    adminCustomerManagementCustomer($store, 'bravo@example.test', 'Bravo Example');

    Livewire::actingAs($user)
        ->test(AdminCustomersIndex::class)
        ->assertSee('Jane Example')
        ->assertSee('Bravo Example')
        ->set('search', 'jane')
        ->assertSee('Jane Example')
        ->assertDontSee('Bravo Example')
        ->set('search', 'bravo@example.test')
        ->assertSee('Bravo Example')
        ->assertDontSee('Jane Example');
});

test('admin customer detail manages addresses', function (): void {
    $store = adminCustomerManagementStore();
    $user = adminCustomerManagementUser();
    $customer = adminCustomerManagementCustomer($store, 'jane@example.test', 'Jane Example');
    $home = CustomerAddress::factory()->default()->create([
        'customer_id' => $customer->getKey(),
        'label' => 'Home',
    ]);

    Livewire::actingAs($user)
        ->test(AdminCustomerShow::class, ['customer' => $customer])
        ->call('openAddressForm')
        ->set('addressLabel', 'Office')
        ->set('addressJson.address1', 'Business Street 5')
        ->set('addressJson.city', 'Berlin')
        ->set('addressJson.postal_code', '10115')
        ->set('addressJson.country', 'DE')
        ->call('saveAddress')
        ->assertHasNoErrors();

    $office = CustomerAddress::query()
        ->where('customer_id', $customer->getKey())
        ->where('label', 'Office')
        ->firstOrFail();

    expect($office->is_default)->toBeFalse();

    Livewire::actingAs($user)
        ->test(AdminCustomerShow::class, ['customer' => $customer])
        ->call('setDefaultAddress', $office->getKey())
        ->assertHasNoErrors()
        ->call('openAddressForm', $office->getKey())
        ->set('addressLabel', 'HQ')
        ->call('saveAddress')
        ->assertHasNoErrors()
        ->call('deleteAddress', $home->getKey())
        ->assertHasNoErrors();

    expect($office->refresh()->is_default)->toBeTrue()
        ->and($office->label)->toBe('HQ')
        ->and(CustomerAddress::query()->whereKey($home->getKey())->exists())->toBeFalse();
});

test('admin customer detail rejects customers from another store', function (): void {
    $store = adminCustomerManagementStore();
    $otherStore = Store::factory()->create();
    $otherCustomer = adminCustomerManagementCustomer($otherStore, 'other@example.test', 'Other Customer');

    app()->instance('current_store', $store);

    Livewire::actingAs(adminCustomerManagementUser())
        ->test(AdminCustomerShow::class, ['customer' => $otherCustomer])
        ->assertStatus(404);
});
