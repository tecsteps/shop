<?php

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\FinancialStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\PaymentFailedException;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\TaxSettings;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create(['default_currency' => 'EUR']);
    app()->instance('current_store', $this->store);
    $product = Product::factory()->for($this->store)->create();
    $this->variant = ProductVariant::factory()->for($product)->create(['price_amount' => 2500, 'requires_shipping' => true]);
    $this->variant->inventoryItem->update(['quantity_on_hand' => 10]);
    $zone = ShippingZone::factory()->for($this->store)->create(['countries_json' => ['DE']]);
    $this->rate = ShippingRate::factory()->for($zone, 'zone')->create(['config_json' => ['amount' => 499]]);
    TaxSettings::factory()->for($this->store)->create(['prices_include_tax' => false, 'config_json' => ['default_rate_bps' => 1900]]);
    $this->cart = app(CartService::class)->create($this->store);
    app(CartService::class)->addLine($this->cart, $this->variant->id, 2);
    $this->service = app(CheckoutService::class);
});

function checkoutAddressData(): array
{
    return ['email' => 'buyer@example.com', 'shipping_address' => ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'address1' => 'Main Street 1', 'city' => 'Berlin', 'country' => 'DE', 'country_code' => 'DE', 'postal_code' => '10115']];
}

it('completes an idempotent paid checkout and commits inventory', function () {
    $checkout = $this->service->create($this->cart);
    $this->service->setAddress($checkout, checkoutAddressData());
    $this->service->setShippingMethod($checkout->refresh(), $this->rate->id);
    $this->service->selectPaymentMethod($checkout->refresh(), PaymentMethod::CreditCard);
    $order = $this->service->completeCheckout($checkout->refresh(), ['card_number' => '4242424242424242']);
    $sameOrder = $this->service->completeCheckout($checkout->refresh(), ['card_number' => '4242424242424242']);

    expect($order->id)->toBe($sameOrder->id)
        ->and($order->financial_status)->toBe(FinancialStatus::Paid)
        ->and($checkout->refresh()->status)->toBe(CheckoutStatus::Completed)
        ->and($this->cart->refresh()->status)->toBe(CartStatus::Converted)
        ->and($this->variant->inventoryItem->refresh()->quantity_on_hand)->toBe(8)
        ->and($this->variant->inventoryItem->quantity_reserved)->toBe(0);
});

it('releases reserved stock when a card is declined', function () {
    $checkout = $this->service->create($this->cart);
    $this->service->setAddress($checkout, checkoutAddressData());
    $this->service->setShippingMethod($checkout->refresh(), $this->rate->id);
    $this->service->selectPaymentMethod($checkout->refresh(), PaymentMethod::CreditCard);

    expect(fn () => $this->service->completeCheckout($checkout->refresh(), ['card_number' => '4000000000000002']))
        ->toThrow(PaymentFailedException::class);
    expect($checkout->refresh()->status)->toBe(CheckoutStatus::ShippingSelected)
        ->and($this->variant->inventoryItem->refresh()->quantity_reserved)->toBe(0);
});

it('creates pending bank transfer orders while retaining reservations', function () {
    $checkout = $this->service->create($this->cart);
    $this->service->setAddress($checkout, checkoutAddressData());
    $this->service->setShippingMethod($checkout->refresh(), $this->rate->id);
    $this->service->selectPaymentMethod($checkout->refresh(), PaymentMethod::BankTransfer);
    $order = $this->service->completeCheckout($checkout->refresh());

    expect($order->financial_status)->toBe(FinancialStatus::Pending)
        ->and($this->variant->inventoryItem->refresh()->quantity_on_hand)->toBe(10)
        ->and($this->variant->inventoryItem->quantity_reserved)->toBe(2);
});
