<?php

use App\Livewire\Storefront\Account\Addresses\Index as AddressesIndex;
use App\Livewire\Storefront\Account\Dashboard;
use App\Livewire\Storefront\Account\Orders\Index as OrdersIndex;
use App\Livewire\Storefront\Account\Orders\Show as OrderShow;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\NavigationMenu;
use App\Models\Order;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\Theme;
use App\Models\ThemeSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create(['default_currency' => 'EUR']);

    StoreDomain::factory()->create([
        'store_id' => $this->store->id,
        'hostname' => 'shop.test',
    ]);

    $theme = Theme::factory()->published()->create([
        'store_id' => $this->store->id,
    ]);

    ThemeSettings::factory()->create([
        'theme_id' => $theme->id,
    ]);

    NavigationMenu::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'main-menu',
        'title' => 'Main Menu',
    ]);

    NavigationMenu::factory()->create([
        'store_id' => $this->store->id,
        'handle' => 'footer-menu',
        'title' => 'Footer Menu',
    ]);

    $this->customer = Customer::factory()->create([
        'store_id' => $this->store->id,
        'name' => 'Jane Doe',
    ]);

    app()->instance('current_store', $this->store);
});

// --- Dashboard ---

it('renders the account dashboard for authenticated customer', function () {
    $this->actingAs($this->customer, 'customer');

    $response = $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->get('/account');

    $response->assertSuccessful()
        ->assertSee('Welcome back, Jane Doe');
});

it('redirects unauthenticated user to login', function () {
    $response = $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->get('/account');

    $response->assertRedirect();
});

it('shows recent orders on dashboard', function () {
    $this->actingAs($this->customer, 'customer');

    Order::factory()->create([
        'store_id' => $this->store->id,
        'customer_id' => $this->customer->id,
        'order_number' => '#1001',
    ]);

    Livewire::test(Dashboard::class)
        ->assertSee('#1001');
});

it('logs out the customer', function () {
    $this->actingAs($this->customer, 'customer');

    Livewire::test(Dashboard::class)
        ->call('logout')
        ->assertRedirect(route('storefront.account.login'));
});

// --- Orders ---

it('renders the order history page', function () {
    $this->actingAs($this->customer, 'customer');

    $response = $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->get('/account/orders');

    $response->assertSuccessful()
        ->assertSee('Order History');
});

it('displays orders belonging to the customer', function () {
    $this->actingAs($this->customer, 'customer');

    Order::factory()->create([
        'store_id' => $this->store->id,
        'customer_id' => $this->customer->id,
        'order_number' => '#2001',
    ]);

    Livewire::test(OrdersIndex::class)
        ->assertSee('#2001');
});

it('shows empty state when customer has no orders', function () {
    $this->actingAs($this->customer, 'customer');

    Livewire::test(OrdersIndex::class)
        ->assertSee('No orders yet');
});

it('renders an order detail page', function () {
    $this->actingAs($this->customer, 'customer');

    Order::factory()->create([
        'store_id' => $this->store->id,
        'customer_id' => $this->customer->id,
        'order_number' => '#3001',
    ]);

    Livewire::test(OrderShow::class, ['orderNumber' => '#3001'])
        ->assertSee('Order #3001');
});

it('returns 404 for another customers order', function () {
    $this->actingAs($this->customer, 'customer');

    $otherCustomer = Customer::factory()->create(['store_id' => $this->store->id]);
    Order::factory()->create([
        'store_id' => $this->store->id,
        'customer_id' => $otherCustomer->id,
        'order_number' => '#4001',
    ]);

    Livewire::test(OrderShow::class, ['orderNumber' => '#4001'])
        ->assertStatus(404);
});

// --- Addresses ---

it('renders the address book page', function () {
    $this->actingAs($this->customer, 'customer');

    $response = $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->get('/account/addresses');

    $response->assertSuccessful()
        ->assertSee('Your Addresses');
});

it('adds a new address', function () {
    $this->actingAs($this->customer, 'customer');

    Livewire::test(AddressesIndex::class)
        ->call('openAddForm')
        ->set('firstName', 'Jane')
        ->set('lastName', 'Doe')
        ->set('address1', '123 Main St')
        ->set('city', 'Berlin')
        ->set('zip', '10115')
        ->set('country', 'DE')
        ->call('saveAddress')
        ->assertSet('showForm', false);

    $this->assertDatabaseHas('customer_addresses', [
        'customer_id' => $this->customer->id,
    ]);
});

it('edits an existing address', function () {
    $this->actingAs($this->customer, 'customer');

    $address = CustomerAddress::factory()->create([
        'customer_id' => $this->customer->id,
    ]);

    Livewire::test(AddressesIndex::class)
        ->call('editAddress', $address->id)
        ->assertSet('showForm', true)
        ->assertSet('editingAddressId', $address->id);
});

it('deletes an address', function () {
    $this->actingAs($this->customer, 'customer');

    $address = CustomerAddress::factory()->create([
        'customer_id' => $this->customer->id,
    ]);

    Livewire::test(AddressesIndex::class)
        ->call('deleteAddress', $address->id);

    $this->assertDatabaseMissing('customer_addresses', [
        'id' => $address->id,
    ]);
});

it('sets an address as default', function () {
    $this->actingAs($this->customer, 'customer');

    $address1 = CustomerAddress::factory()->default()->create([
        'customer_id' => $this->customer->id,
    ]);

    $address2 = CustomerAddress::factory()->create([
        'customer_id' => $this->customer->id,
    ]);

    Livewire::test(AddressesIndex::class)
        ->call('setDefault', $address2->id);

    expect($address1->fresh()->is_default)->toBeFalse()
        ->and($address2->fresh()->is_default)->toBeTrue();
});
