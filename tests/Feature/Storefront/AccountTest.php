<?php

use App\Livewire\Storefront\Account\Addresses\Index as AddressesIndex;
use App\Livewire\Storefront\Account\Dashboard;
use App\Livewire\Storefront\Account\Orders\Index as OrdersIndex;
use App\Livewire\Storefront\Account\Orders\Show as OrdersShow;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);

    $this->customer = Customer::factory()->for($this->store)->create([
        'name' => 'Grace Hopper',
        'email' => 'grace@example.com',
    ]);

    Auth::guard('customer')->login($this->customer);
});

afterEach(function (): void {
    Auth::guard('customer')->logout();
    app()->forgetInstance('current_store');
});

it('renders the account dashboard for a logged-in customer', function (): void {
    Livewire::test(Dashboard::class)
        ->assertStatus(200)
        ->assertSee('Grace Hopper')
        ->assertSee('grace@example.com');
});

it('lists the customer orders', function (): void {
    Order::factory()->for($this->store)->create([
        'customer_id' => $this->customer->id,
        'order_number' => '#3001',
    ]);
    Order::factory()->for($this->store)->create([
        'customer_id' => $this->customer->id,
        'order_number' => '#3002',
    ]);

    Livewire::test(OrdersIndex::class)
        ->assertStatus(200)
        ->assertSee('#3001')
        ->assertSee('#3002');
});

it('shows a single customer order', function (): void {
    Order::factory()->for($this->store)->create([
        'customer_id' => $this->customer->id,
        'order_number' => '#4001',
        'email' => 'grace@example.com',
    ]);

    Livewire::test(OrdersShow::class, ['orderNumber' => '4001'])
        ->assertStatus(200)
        ->assertSee('#4001');
});

it('supports adding and deleting addresses', function (): void {
    $component = Livewire::test(AddressesIndex::class)
        ->set('label', 'Work')
        ->set('firstName', 'Grace')
        ->set('lastName', 'Hopper')
        ->set('line1', 'Harvard Yard')
        ->set('city', 'Cambridge')
        ->set('postalCode', '02138')
        ->set('country', 'US')
        ->call('addAddress')
        ->assertHasNoErrors();

    expect(CustomerAddress::query()->where('customer_id', $this->customer->id)->count())->toBe(1);

    $addressId = CustomerAddress::query()->where('customer_id', $this->customer->id)->value('id');
    $component->call('deleteAddress', $addressId);

    expect(CustomerAddress::query()->where('customer_id', $this->customer->id)->count())->toBe(0);
});
