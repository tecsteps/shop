<?php

use App\Livewire\Storefront\Account\Addresses\Index as AddressesIndex;
use App\Livewire\Storefront\Account\Dashboard;
use App\Livewire\Storefront\Account\Orders\Index as OrdersIndex;
use App\Livewire\Storefront\Account\Orders\Show as OrdersShow;
use App\Livewire\Storefront\Account\Profile;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Services\CheckoutService;
use App\Services\OrderService;
use App\ValueObjects\PaymentResult;
use Livewire\Livewire;
use Tests\Support\CommerceTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function accountAddress(): array
{
    return CommerceTestHelper::address();
}

function placeOrderForCustomer(App\Models\Store $store, Customer $customer): Order
{
    $cart = CommerceTestHelper::cartWithLines($store);
    $checkout = app(CheckoutService::class)->createFromCart($cart, $customer->email, $customer);
    $checkout->payment_method = App\Enums\PaymentMethod::CreditCard;
    $checkout->save();

    return app(OrderService::class)->createFromCheckout(
        $checkout->refresh(),
        new PaymentResult(success: true, referenceId: 'mock_test', status: 'captured')
    );
}

it('redirects guests away from account pages', function () {
    ['domain' => $domain] = $this->createStoreContext();

    $this->get(storefrontUrl($domain->hostname, '/account'))->assertRedirect(storefrontUrl($domain->hostname, '/account/login'));
    $this->get(storefrontUrl($domain->hostname, '/account/orders'))->assertRedirect(storefrontUrl($domain->hostname, '/account/login'));
    $this->get(storefrontUrl($domain->hostname, '/account/addresses'))->assertRedirect(storefrontUrl($domain->hostname, '/account/login'));
    $this->get(storefrontUrl($domain->hostname, '/account/profile'))->assertRedirect(storefrontUrl($domain->hostname, '/account/login'));
});

it('renders the account dashboard with recent orders', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $store->id, 'name' => 'Jane Doe']);
    $order = placeOrderForCustomer($store, $customer);

    $response = $this->actingAs($customer, 'customer')->get(storefrontUrl($domain->hostname, '/account'));

    $response->assertOk();
    $response->assertSee('Welcome back, Jane Doe!');
    $response->assertSee($order->order_number);
});

it('renders the dashboard component', function () {
    ['store' => $store] = $this->createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $store->id]);
    $this->actingAs($customer, 'customer');

    Livewire::test(Dashboard::class)->assertSee('Order history')->assertStatus(200);
});

it('lists customer orders without leaking other customers orders', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $store->id]);
    $other = Customer::factory()->create(['store_id' => $store->id]);
    $mine = placeOrderForCustomer($store, $customer);
    $theirs = placeOrderForCustomer($store, $other);

    $response = $this->actingAs($customer, 'customer')->get(storefrontUrl($domain->hostname, '/account/orders'));

    $response->assertOk();
    $response->assertSee($mine->order_number);
    $response->assertDontSee($theirs->order_number);

    Livewire::test(OrdersIndex::class)->assertSee($mine->order_number)->assertStatus(200);
});

it('shows order detail for the owning customer only', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $store->id]);
    $other = Customer::factory()->create(['store_id' => $store->id]);
    $mine = placeOrderForCustomer($store, $customer);
    $theirs = placeOrderForCustomer($store, $other);

    $url = fn (string $number): string => storefrontUrl($domain->hostname, '/account/orders/'.urlencode($number));

    $this->actingAs($customer, 'customer')->get($url($mine->order_number))->assertOk()->assertSee($mine->order_number);
    $this->actingAs($customer, 'customer')->get($url($theirs->order_number))->assertNotFound();

    Livewire::test(OrdersShow::class, ['orderNumber' => $mine->order_number])
        ->assertSee($mine->order_number)
        ->assertStatus(200);
});

it('updates the customer profile with per-store email uniqueness', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $store->id]);
    $sibling = Customer::factory()->create(['store_id' => $store->id]);
    $this->actingAs($customer, 'customer');

    $this->get(storefrontUrl($domain->hostname, '/account/profile'))->assertOk();

    Livewire::test(Profile::class)
        ->set('name', 'Janet Doe')
        ->set('email', $sibling->email)
        ->call('save')
        ->assertHasErrors('email');

    Livewire::test(Profile::class)
        ->set('name', 'Janet Doe')
        ->set('email', 'janet@example.com')
        ->set('marketingOptIn', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('status', 'Profile updated successfully.');

    expect($customer->refresh()->name)->toBe('Janet Doe');
    expect($customer->refresh()->marketing_opt_in)->toBeTrue();
});

it('manages addresses with a single default', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $store->id]);
    $this->actingAs($customer, 'customer');

    $this->get(storefrontUrl($domain->hostname, '/account/addresses'))->assertOk();

    Livewire::test(AddressesIndex::class)
        ->call('openCreate')
        ->assertSet('showModal', true)
        ->set('label', 'Home')
        ->set('address', accountAddress())
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    $first = CustomerAddress::query()->where('customer_id', $customer->id)->first();

    expect($first->is_default)->toBeTrue();

    Livewire::test(AddressesIndex::class)
        ->call('openCreate')
        ->set('label', 'Work')
        ->set('address', accountAddress())
        ->call('save')
        ->assertHasNoErrors();

    $second = CustomerAddress::query()->where('customer_id', $customer->id)->where('label', 'Work')->first();

    expect($second->is_default)->toBeFalse();

    Livewire::test(AddressesIndex::class)
        ->call('setDefault', $second->id);

    expect($first->refresh()->is_default)->toBeFalse();
    expect($second->refresh()->is_default)->toBeTrue();

    Livewire::test(AddressesIndex::class)
        ->call('openEdit', $second->id)
        ->assertSet('editingId', $second->id)
        ->set('label', 'Office')
        ->call('save')
        ->assertHasNoErrors();

    expect($second->refresh()->label)->toBe('Office');

    Livewire::test(AddressesIndex::class)
        ->call('confirmDelete', $second->id)
        ->call('delete');

    expect(CustomerAddress::query()->whereKey($second->id)->exists())->toBeFalse();
    expect($first->refresh()->is_default)->toBeTrue();
});

it('validates address fields', function () {
    ['store' => $store] = $this->createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $store->id]);
    $this->actingAs($customer, 'customer');

    Livewire::test(AddressesIndex::class)
        ->call('openCreate')
        ->set('address.first_name', '')
        ->set('address.country_code', 'XXL')
        ->call('save')
        ->assertHasErrors(['address.first_name', 'address.country_code']);
});
