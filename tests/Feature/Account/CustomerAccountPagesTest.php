<?php

use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Livewire\Storefront\Account\Addresses\Index as AddressesIndex;
use App\Livewire\Storefront\Account\Dashboard;
use App\Livewire\Storefront\Account\Orders\Index as OrdersIndex;
use App\Livewire\Storefront\Account\Orders\Show as OrdersShow;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Fulfillment;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

// --- Dashboard ---

it('shows the dashboard for authenticated customer', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);

    Livewire::actingAs($customer, 'customer')
        ->test(Dashboard::class)
        ->assertSee('Welcome')
        ->assertSee($customer->name)
        ->assertSee($customer->email)
        ->assertStatus(200);
});

it('redirects unauthenticated users to login from account dashboard', function () {
    $ctx = createStoreContext('account-store.test');

    $response = $this->get('http://account-store.test/account');

    $response->assertRedirect('/account/login');
});

it('shows recent orders on dashboard', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);

    $orders = Order::factory()->count(3)->create([
        'store_id' => $ctx['store']->id,
        'customer_id' => $customer->id,
    ]);

    Livewire::actingAs($customer, 'customer')
        ->test(Dashboard::class)
        ->assertSee($orders[0]->order_number)
        ->assertSee($orders[1]->order_number)
        ->assertSee($orders[2]->order_number);
});

it('limits dashboard to 5 recent orders', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);

    Order::factory()->count(7)->create([
        'store_id' => $ctx['store']->id,
        'customer_id' => $customer->id,
        'placed_at' => now(),
    ]);

    $dashboard = new Dashboard;
    $this->actingAs($customer, 'customer');

    expect($customer->orders()->latest('placed_at')->limit(5)->get())->toHaveCount(5);
});

it('shows empty state when customer has no orders', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);

    Livewire::actingAs($customer, 'customer')
        ->test(Dashboard::class)
        ->assertSee('You have no orders yet.');
});

// --- Order History ---

it('shows order history page', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);

    $order = Order::factory()->create([
        'store_id' => $ctx['store']->id,
        'customer_id' => $customer->id,
    ]);

    Livewire::actingAs($customer, 'customer')
        ->test(OrdersIndex::class)
        ->assertSee($order->order_number)
        ->assertStatus(200);
});

it('redirects unauthenticated users to login from orders page', function () {
    $ctx = createStoreContext('orders-store.test');

    $response = $this->get('http://orders-store.test/account/orders');

    $response->assertRedirect('/account/login');
});

it('only shows customer own orders', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);
    $otherCustomer = Customer::factory()->create(['store_id' => $ctx['store']->id]);

    $myOrder = Order::factory()->create([
        'store_id' => $ctx['store']->id,
        'customer_id' => $customer->id,
    ]);
    $otherOrder = Order::factory()->create([
        'store_id' => $ctx['store']->id,
        'customer_id' => $otherCustomer->id,
    ]);

    Livewire::actingAs($customer, 'customer')
        ->test(OrdersIndex::class)
        ->assertSee($myOrder->order_number)
        ->assertDontSee($otherOrder->order_number);
});

it('shows order status and fulfillment status in order history', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);

    Order::factory()->paid()->create([
        'store_id' => $ctx['store']->id,
        'customer_id' => $customer->id,
        'fulfillment_status' => FulfillmentStatus::Unfulfilled,
    ]);

    Livewire::actingAs($customer, 'customer')
        ->test(OrdersIndex::class)
        ->assertSee('Paid')
        ->assertSee('Unfulfilled');
});

// --- Order Detail ---

it('shows order detail page', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);

    $order = Order::factory()->create([
        'store_id' => $ctx['store']->id,
        'customer_id' => $customer->id,
        'order_number' => '#2001',
    ]);

    OrderLine::factory()->create([
        'order_id' => $order->id,
        'title_snapshot' => 'Test Product',
        'quantity' => 2,
        'total_amount' => 5000,
    ]);

    Livewire::actingAs($customer, 'customer')
        ->test(OrdersShow::class, ['orderNumber' => '2001'])
        ->assertSee('#2001')
        ->assertSee('Test Product')
        ->assertStatus(200);
});

it('shows order detail with hash-prefixed order number', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);

    Order::factory()->create([
        'store_id' => $ctx['store']->id,
        'customer_id' => $customer->id,
        'order_number' => '#2010',
    ]);

    Livewire::actingAs($customer, 'customer')
        ->test(OrdersShow::class, ['orderNumber' => '#2010'])
        ->assertSee('#2010')
        ->assertStatus(200);
});

it('shows payment info on order detail', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);

    $order = Order::factory()->create([
        'store_id' => $ctx['store']->id,
        'customer_id' => $customer->id,
        'order_number' => '#2002',
    ]);

    Payment::factory()->create([
        'order_id' => $order->id,
        'method' => PaymentMethod::CreditCard,
        'status' => PaymentStatus::Captured,
        'amount' => 6449,
        'currency' => 'EUR',
    ]);

    Livewire::actingAs($customer, 'customer')
        ->test(OrdersShow::class, ['orderNumber' => '2002'])
        ->assertSee('Payment')
        ->assertSee('Captured');
});

it('shows fulfillment tracking on order detail', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);

    $order = Order::factory()->fulfilled()->create([
        'store_id' => $ctx['store']->id,
        'customer_id' => $customer->id,
        'order_number' => '#2003',
    ]);

    Fulfillment::factory()->create([
        'order_id' => $order->id,
        'status' => FulfillmentShipmentStatus::Shipped,
        'tracking_company' => 'DHL',
        'tracking_number' => 'DHL123456',
        'shipped_at' => now(),
    ]);

    Livewire::actingAs($customer, 'customer')
        ->test(OrdersShow::class, ['orderNumber' => '2003'])
        ->assertSee('DHL')
        ->assertSee('DHL123456');
});

it('shows shipping address on order detail', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);

    Order::factory()->create([
        'store_id' => $ctx['store']->id,
        'customer_id' => $customer->id,
        'order_number' => '#2004',
        'shipping_address_json' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => 'Musterstr. 1',
            'city' => 'Berlin',
            'postal_code' => '10115',
            'country_code' => 'DE',
        ],
    ]);

    Livewire::actingAs($customer, 'customer')
        ->test(OrdersShow::class, ['orderNumber' => '2004'])
        ->assertSee('Musterstr. 1')
        ->assertSee('Berlin');
});

it('prevents customer from viewing another customer order', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);
    $otherCustomer = Customer::factory()->create(['store_id' => $ctx['store']->id]);

    Order::factory()->create([
        'store_id' => $ctx['store']->id,
        'customer_id' => $otherCustomer->id,
        'order_number' => '#9999',
    ]);

    $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    Livewire::actingAs($customer, 'customer')
        ->test(OrdersShow::class, ['orderNumber' => '9999']);
});

it('redirects unauthenticated users to login from order detail', function () {
    $ctx = createStoreContext('detail-store.test');

    $response = $this->get('http://detail-store.test/account/orders/2001');

    $response->assertRedirect('/account/login');
});

// --- Address Book ---

it('shows address book page', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);

    CustomerAddress::factory()->create([
        'customer_id' => $customer->id,
        'label' => 'Home',
        'address_json' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'Berlin',
            'postal_code' => '10115',
            'country_code' => 'DE',
        ],
    ]);

    Livewire::actingAs($customer, 'customer')
        ->test(AddressesIndex::class)
        ->assertSee('Home')
        ->assertSee('123 Main St')
        ->assertSee('Berlin')
        ->assertStatus(200);
});

it('adds a new address', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);

    Livewire::actingAs($customer, 'customer')
        ->test(AddressesIndex::class)
        ->call('openAddForm')
        ->set('label', 'Work')
        ->set('firstName', 'Jane')
        ->set('lastName', 'Smith')
        ->set('address1', '456 Office Blvd')
        ->set('city', 'Munich')
        ->set('postalCode', '80331')
        ->set('countryCode', 'DE')
        ->call('saveAddress')
        ->assertHasNoErrors();

    expect(CustomerAddress::where('customer_id', $customer->id)->count())->toBe(1);

    $address = CustomerAddress::where('customer_id', $customer->id)->first();
    expect($address->label)->toBe('Work')
        ->and($address->address_json['first_name'])->toBe('Jane')
        ->and($address->address_json['city'])->toBe('Munich');
});

it('validates required fields when adding address', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);

    Livewire::actingAs($customer, 'customer')
        ->test(AddressesIndex::class)
        ->call('openAddForm')
        ->call('saveAddress')
        ->assertHasErrors(['firstName', 'lastName', 'address1', 'city', 'postalCode']);
});

it('edits an existing address', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);

    $address = CustomerAddress::factory()->create([
        'customer_id' => $customer->id,
        'label' => 'Home',
        'address_json' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'Berlin',
            'postal_code' => '10115',
            'country_code' => 'DE',
        ],
    ]);

    Livewire::actingAs($customer, 'customer')
        ->test(AddressesIndex::class)
        ->call('editAddress', $address->id)
        ->set('city', 'Hamburg')
        ->set('postalCode', '20095')
        ->call('saveAddress')
        ->assertHasNoErrors();

    $address->refresh();
    expect($address->address_json['city'])->toBe('Hamburg')
        ->and($address->address_json['postal_code'])->toBe('20095');
});

it('deletes an address', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);

    $address = CustomerAddress::factory()->create([
        'customer_id' => $customer->id,
    ]);

    Livewire::actingAs($customer, 'customer')
        ->test(AddressesIndex::class)
        ->call('deleteAddress', $address->id);

    expect(CustomerAddress::find($address->id))->toBeNull();
});

it('sets an address as default', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);

    $addr1 = CustomerAddress::factory()->default()->create(['customer_id' => $customer->id]);
    $addr2 = CustomerAddress::factory()->create(['customer_id' => $customer->id]);

    Livewire::actingAs($customer, 'customer')
        ->test(AddressesIndex::class)
        ->call('setDefault', $addr2->id);

    expect($addr1->fresh()->is_default)->toBeFalse()
        ->and($addr2->fresh()->is_default)->toBeTrue();
});

it('shows default badge on default address', function () {
    $ctx = createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $ctx['store']->id]);

    CustomerAddress::factory()->default()->create([
        'customer_id' => $customer->id,
        'address_json' => [
            'first_name' => 'Default',
            'last_name' => 'Addr',
            'address1' => '1 Default St',
            'city' => 'Berlin',
            'postal_code' => '10115',
            'country_code' => 'DE',
        ],
    ]);

    Livewire::actingAs($customer, 'customer')
        ->test(AddressesIndex::class)
        ->assertSee('Default');
});

it('redirects unauthenticated users to login from addresses page', function () {
    $ctx = createStoreContext('addr-store.test');

    $response = $this->get('http://addr-store.test/account/addresses');

    $response->assertRedirect('/account/login');
});

// --- Logout ---

it('logs out customer and redirects to login', function () {
    $ctx = createStoreContext('logout-store.test');
    $customer = Customer::factory()->create([
        'store_id' => $ctx['store']->id,
        'password_hash' => Hash::make('password'),
    ]);

    $response = $this->actingAs($customer, 'customer')
        ->post('http://logout-store.test/account/logout');

    $response->assertRedirect('/account/login');
    $this->assertGuest('customer');
});
