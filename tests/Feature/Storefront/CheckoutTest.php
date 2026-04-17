<?php

use App\Enums\FinancialStatus;
use App\Livewire\Storefront\Checkout\Confirmation;
use App\Livewire\Storefront\Checkout\Show;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Services\CartService;
use App\Support\CartSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);

    $product = Product::factory()->for($this->store)->create(['title' => 'Cotton Tee']);
    $this->variant = ProductVariant::factory()->for($product)->create([
        'price_amount' => 2500,
        'weight_g' => 300,
        'requires_shipping' => true,
    ]);
    InventoryItem::factory()
        ->for($this->store)
        ->for($this->variant, 'variant')
        ->create(['quantity_on_hand' => 10]);

    $zone = ShippingZone::factory()->for($this->store)->create([
        'countries_json' => ['DE'],
        'regions_json' => [],
    ]);
    $this->rate = ShippingRate::factory()->for($zone, 'zone')->flat(599)->create();
});

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

function seedCheckoutCart(Store $store, ProductVariant $variant): void
{
    $cart = CartSession::getOrCreate($store);
    app(CartService::class)->addLine($cart, $variant->id, 2);
}

it('redirects to the cart when the cart is empty', function (): void {
    Livewire::test(Show::class)
        ->assertRedirect(route('storefront.cart.show'));
});

it('completes a credit card checkout end-to-end', function (): void {
    seedCheckoutCart($this->store, $this->variant);

    Livewire::test(Show::class)
        ->set('email', 'buyer@example.com')
        ->set('shippingAddress.first_name', 'Jane')
        ->set('shippingAddress.last_name', 'Doe')
        ->set('shippingAddress.line1', 'Karl-Marx-Allee 1')
        ->set('shippingAddress.city', 'Berlin')
        ->set('shippingAddress.postal_code', '10178')
        ->set('shippingAddress.country', 'DE')
        ->call('continueToShipping')
        ->assertHasNoErrors()
        ->set('shippingMethodId', $this->rate->id)
        ->call('continueToPayment')
        ->assertHasNoErrors()
        ->set('paymentMethod', 'credit_card')
        ->call('placeOrder')
        ->assertHasNoErrors();

    $order = Order::query()->latest('id')->first();
    expect($order)->not->toBeNull()
        ->and($order->financial_status)->toBe(FinancialStatus::Paid)
        ->and($order->email)->toBe('buyer@example.com');

    expect(CartSession::current())->toBeNull();
});

it('leaves the order pending for bank transfer', function (): void {
    seedCheckoutCart($this->store, $this->variant);

    Livewire::test(Show::class)
        ->set('email', 'bank@example.com')
        ->set('shippingAddress.first_name', 'Max')
        ->set('shippingAddress.last_name', 'Muster')
        ->set('shippingAddress.line1', 'Hauptstr 1')
        ->set('shippingAddress.city', 'Berlin')
        ->set('shippingAddress.postal_code', '10115')
        ->set('shippingAddress.country', 'DE')
        ->call('continueToShipping')
        ->set('shippingMethodId', $this->rate->id)
        ->call('continueToPayment')
        ->set('paymentMethod', 'bank_transfer')
        ->call('placeOrder')
        ->assertHasNoErrors();

    $order = Order::query()->latest('id')->first();
    expect($order)->not->toBeNull()
        ->and($order->financial_status)->toBe(FinancialStatus::Pending);
});

it('renders the confirmation page for an order', function (): void {
    $order = Order::factory()->for($this->store)->create([
        'order_number' => '#2001',
        'email' => 'confirm@example.com',
    ]);

    Livewire::test(Confirmation::class, ['order_number' => '2001'])
        ->assertStatus(200)
        ->assertSee('Thank you')
        ->assertSee('#2001');
});
