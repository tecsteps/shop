<?php

use App\Livewire\Admin\Customers\Index;
use App\Livewire\Admin\Customers\Show;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    ['store' => $this->store, 'user' => $this->user] = $this->createStoreContext();
    $this->actingAsAdmin($this->user, $this->store);
});

it('lists customers scoped to the current store', function () {
    Customer::factory()->create(['store_id' => $this->store->getKey(), 'name' => 'Jane Doe']);
    ['store' => $otherStore] = $this->createStoreContext();
    Customer::factory()->create(['store_id' => $otherStore->getKey(), 'name' => 'Other Store Customer']);

    $this->get(route('admin.customers.index'))
        ->assertOk()
        ->assertSee('Jane Doe')
        ->assertDontSee('Other Store Customer');
});

it('searches customers by name and email', function () {
    Customer::factory()->create(['store_id' => $this->store->getKey(), 'name' => 'Alice', 'email' => 'alice@example.com']);
    Customer::factory()->create(['store_id' => $this->store->getKey(), 'name' => 'Bob', 'email' => 'bob@example.com']);

    Livewire::test(Index::class)
        ->set('search', 'alice')
        ->assertSee('Alice')
        ->assertDontSee('Bob');
});

it('shows customer detail with addresses', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->getKey()]);
    CustomerAddress::factory()->create(['customer_id' => $customer->getKey(), 'label' => 'Home', 'is_default' => true]);

    $this->get(route('admin.customers.show', $customer))
        ->assertOk()
        ->assertSee('Home');
});

it('adds an address to a customer', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->getKey()]);

    Livewire::test(Show::class, ['customer' => $customer])
        ->call('openAddressForm')
        ->set('addressLabel', 'Work')
        ->set('addressForm.first_name', 'Jane')
        ->set('addressForm.last_name', 'Doe')
        ->set('addressForm.address1', 'Main Street 1')
        ->set('addressForm.city', 'Berlin')
        ->set('addressForm.zip', '10115')
        ->set('addressForm.country_code', 'DE')
        ->call('saveAddress')
        ->assertDispatched('toast');

    $address = CustomerAddress::query()->where('customer_id', $customer->getKey())->where('label', 'Work')->firstOrFail();

    expect($address->address_json['city'])->toBe('Berlin')
        ->and((bool) $address->is_default)->toBeTrue();
});

it('deletes an address and promotes another to default', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->getKey()]);
    $default = CustomerAddress::factory()->create(['customer_id' => $customer->getKey(), 'is_default' => true]);
    $other = CustomerAddress::factory()->create(['customer_id' => $customer->getKey(), 'is_default' => false]);

    Livewire::test(Show::class, ['customer' => $customer])
        ->call('deleteAddress', $default->getKey())
        ->assertDispatched('toast');

    expect(CustomerAddress::query()->whereKey($default->getKey())->exists())->toBeFalse()
        ->and((bool) $other->refresh()->is_default)->toBeTrue();
});

it('returns 404 for a customer from another store', function () {
    ['store' => $otherStore] = $this->createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $otherStore->getKey()]);

    $this->get(route('admin.customers.show', $customer))->assertNotFound();
});
