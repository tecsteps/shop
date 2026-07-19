<?php

use App\Livewire\Admin\Customers\Index;
use App\Livewire\Admin\Customers\Show;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use Livewire\Livewire;

beforeEach(function () {
    $this->store = $this->createStore();
    $this->user = $this->createUserWithRole($this->store, 'owner');
    $this->bindStore($this->store);
});

test('lists customers with order stats', function () {
    $customer = Customer::factory()->create([
        'store_id' => $this->store->id,
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'marketing_opt_in' => true,
    ]);

    Order::factory()->paid()->withLines([
        ['quantity' => 1, 'unit_price_amount' => 2000],
    ])->create(['store_id' => $this->store->id, 'customer_id' => $customer->id]);
    Order::factory()->paid()->withLines([
        ['quantity' => 1, 'unit_price_amount' => 1000],
    ])->create(['store_id' => $this->store->id, 'customer_id' => $customer->id]);

    $this->actingAs($this->user)
        ->withSession(['current_store_id' => $this->store->id])
        ->get('/admin/customers')
        ->assertOk()
        ->assertSee('Jane Smith')
        ->assertSee('jane@example.com')
        ->assertSee('30.00 USD') // total spent
        ->assertSee('Opted in');
});

test('searches customers by name or email', function () {
    Customer::factory()->create([
        'store_id' => $this->store->id,
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
    ]);
    Customer::factory()->create([
        'store_id' => $this->store->id,
        'name' => 'Bob Jones',
        'email' => 'bob@example.com',
    ]);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->set('search', 'Jane')
        ->assertSee('Jane Smith')
        ->assertDontSee('Bob Jones')
        ->set('search', 'bob@example.com')
        ->assertSee('Bob Jones')
        ->assertDontSee('Jane Smith');
});

test('shows customer detail with orders and addresses', function () {
    $customer = Customer::factory()->create([
        'store_id' => $this->store->id,
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
    ]);

    CustomerAddress::factory()->default()->create([
        'customer_id' => $customer->id,
        'label' => 'Home',
        'address_json' => [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'address1' => '123 Main St',
            'city' => 'Springfield',
            'postal_code' => '62701',
            'country' => 'United States',
        ],
    ]);

    Order::factory()->paid()->withLines([
        ['quantity' => 1, 'unit_price_amount' => 8900],
    ])->create([
        'store_id' => $this->store->id,
        'customer_id' => $customer->id,
        'order_number' => '#1005',
    ]);

    $this->actingAs($this->user)
        ->withSession(['current_store_id' => $this->store->id])
        ->get('/admin/customers/'.$customer->id)
        ->assertOk()
        ->assertSee('Jane Smith')
        ->assertSee('jane@example.com')
        ->assertSee('#1005')
        ->assertSee('89.00 USD')
        ->assertSee('Home')
        ->assertSee('123 Main St')
        ->assertSee('Springfield')
        ->assertSee('Default');
});

test('updates customer name and marketing opt-in', function () {
    $customer = Customer::factory()->create([
        'store_id' => $this->store->id,
        'name' => 'Old Name',
        'marketing_opt_in' => false,
    ]);

    Livewire::actingAs($this->user);
    Livewire::test(Show::class, ['customer' => $customer])
        ->call('openEditModal')
        ->assertSet('showEditModal', true)
        ->assertSet('name', 'Old Name')
        ->set('name', 'New Name')
        ->set('marketingOptIn', true)
        ->call('saveCustomer')
        ->assertHasNoErrors()
        ->assertDispatched('toast', type: 'success', message: 'Customer saved');

    $customer->refresh();

    expect($customer->name)->toBe('New Name')
        ->and($customer->marketing_opt_in)->toBeTrue();
});

test('support can view a customer but cannot update', function () {
    $support = $this->createUserWithRole($this->store, 'support');

    $customer = Customer::factory()->create([
        'store_id' => $this->store->id,
        'name' => 'Jane Smith',
    ]);

    $this->actingAs($support)
        ->withSession(['current_store_id' => $this->store->id])
        ->get('/admin/customers/'.$customer->id)
        ->assertOk()
        ->assertSee('Jane Smith');

    Livewire::actingAs($support);
    Livewire::test(Show::class, ['customer' => $customer])
        ->call('openEditModal')
        ->assertForbidden();

    Livewire::test(Show::class, ['customer' => $customer])
        ->set('name', 'Hacked Name')
        ->call('saveCustomer')
        ->assertForbidden();

    expect($customer->refresh()->name)->toBe('Jane Smith');
});

test('customer pages are store-scoped', function () {
    $otherStore = $this->createStore();
    $otherCustomer = Customer::factory()->create([
        'store_id' => $otherStore->id,
        'name' => 'Foreign Customer',
        'email' => 'foreign@example.com',
    ]);

    // The store scope hides customers of other stores entirely.
    $this->actingAs($this->user)
        ->withSession(['current_store_id' => $this->store->id])
        ->get('/admin/customers/'.$otherCustomer->id)
        ->assertNotFound();
});

test('guests are redirected from admin customer pages', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);

    $this->get('/admin/customers')->assertRedirect('/admin/login');
    $this->get('/admin/customers/'.$customer->id)->assertRedirect('/admin/login');
});
