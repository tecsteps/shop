<?php

use App\Livewire\Admin\Customers\Index;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->store = Store::factory()->create();
    $this->user->stores()->attach($this->store->id, ['role' => 'owner']);
    $this->actingAs($this->user);
    session(['current_store_id' => $this->store->id]);
    app()->instance('current_store', $this->store);
});

test('customers index page can be rendered', function () {
    $response = $this->get(route('admin.customers.index'));

    $response->assertOk();
    $response->assertSeeLivewire(Index::class);
});

test('customers index displays customers', function () {
    $customer = Customer::factory()->create([
        'store_id' => $this->store->id,
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
    ]);

    Livewire::test(Index::class)
        ->assertSee('Jane Doe')
        ->assertSee('jane@example.com');
});

test('customers index can search by first name', function () {
    Customer::factory()->create([
        'store_id' => $this->store->id,
        'first_name' => 'Alice',
        'last_name' => 'Smith',
    ]);
    Customer::factory()->create([
        'store_id' => $this->store->id,
        'first_name' => 'Bob',
        'last_name' => 'Jones',
    ]);

    Livewire::test(Index::class)
        ->set('search', 'Alice')
        ->assertSee('Alice Smith')
        ->assertDontSee('Bob Jones');
});

test('customers index can search by last name', function () {
    Customer::factory()->create([
        'store_id' => $this->store->id,
        'first_name' => 'Alice',
        'last_name' => 'Smith',
    ]);
    Customer::factory()->create([
        'store_id' => $this->store->id,
        'first_name' => 'Bob',
        'last_name' => 'Jones',
    ]);

    Livewire::test(Index::class)
        ->set('search', 'Jones')
        ->assertDontSee('Alice Smith')
        ->assertSee('Bob Jones');
});

test('customers index can search by email', function () {
    Customer::factory()->create([
        'store_id' => $this->store->id,
        'first_name' => 'Alice',
        'last_name' => 'Smith',
        'email' => 'alice@example.com',
    ]);
    Customer::factory()->create([
        'store_id' => $this->store->id,
        'first_name' => 'Bob',
        'last_name' => 'Jones',
        'email' => 'bob@example.com',
    ]);

    Livewire::test(Index::class)
        ->set('search', 'alice@example')
        ->assertSee('Alice Smith')
        ->assertDontSee('Bob Jones');
});

test('customers index displays order count', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);
    Order::factory()->count(3)->create([
        'store_id' => $this->store->id,
        'customer_id' => $customer->id,
    ]);

    Livewire::test(Index::class)
        ->assertSee('3');
});

test('customers index displays total spent', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);
    Order::factory()->create([
        'store_id' => $this->store->id,
        'customer_id' => $customer->id,
        'total_amount' => 10000,
    ]);

    Livewire::test(Index::class)
        ->assertSee('100.00 EUR');
});

test('customers index paginates at 15 per page', function () {
    Customer::factory()->count(20)->create(['store_id' => $this->store->id]);

    Livewire::test(Index::class)
        ->assertViewHas('customers', fn ($customers) => $customers->count() === 15);
});

test('customers index does not show customers from other stores', function () {
    $otherStore = Store::factory()->create();
    Customer::factory()->create([
        'store_id' => $otherStore->id,
        'first_name' => 'OtherStore',
        'last_name' => 'Customer',
    ]);

    Livewire::test(Index::class)
        ->assertDontSee('OtherStore Customer');
});
