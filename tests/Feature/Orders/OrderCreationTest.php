<?php

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\InventoryPolicy;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\ProductStatus;
use App\Enums\ShippingRateType;
use App\Enums\TaxMode;
use App\Enums\VariantStatus;
use App\Events\OrderCreated;
use App\Models\Checkout;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\TaxSettings;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Support\Facades\Event;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->store = $this->ctx['store'];
    $this->cartService = app(CartService::class);
    $this->checkoutService = app(CheckoutService::class);
});

// -- Helpers --

function createOrderVariant($store, int $price = 2500, int $stock = 20): ProductVariant
{
    $product = Product::factory()->create([
        'store_id' => $store->id,
        'status' => ProductStatus::Active,
    ]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => $price,
        'status' => VariantStatus::Active,
    ]);
    InventoryItem::factory()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => $stock,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Deny,
    ]);

    return $variant;
}

function setupShippingAndTax($store): ShippingRate
{
    $existingRate = ShippingRate::whereHas('zone', fn ($q) => $q->where('store_id', $store->id))
        ->where('is_active', true)
        ->first();

    if ($existingRate) {
        return $existingRate;
    }

    $zone = ShippingZone::create([
        'store_id' => $store->id,
        'name' => 'Domestic',
        'countries_json' => ['DE'],
        'regions_json' => [],
    ]);
    $rate = ShippingRate::create([
        'zone_id' => $zone->id,
        'name' => 'Standard',
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 499],
        'is_active' => true,
    ]);

    if (! TaxSettings::where('store_id', $store->id)->exists()) {
        TaxSettings::create([
            'store_id' => $store->id,
            'mode' => TaxMode::Manual,
            'provider' => 'none',
            'prices_include_tax' => false,
            'config_json' => ['default_rate' => 1900, 'default_name' => 'VAT'],
        ]);
    }

    return $rate;
}

function completeCheckoutForOrder($store, $cartService, $checkoutService, array $options = []): Order
{
    $rate = setupShippingAndTax($store);
    $variant = $options['variant'] ?? createOrderVariant($store);
    $quantity = $options['quantity'] ?? 1;
    $paymentMethod = $options['payment_method'] ?? PaymentMethod::CreditCard;
    $customer = $options['customer'] ?? null;

    $cart = $cartService->create($store, $customer);
    $cartService->addLine($cart, $variant->id, $quantity);

    $checkout = Checkout::create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'customer_id' => $customer?->id,
        'status' => CheckoutStatus::Started,
    ]);

    $checkout = $checkoutService->setAddress($checkout, [
        'email' => 'buyer@example.com',
        'shipping_address' => [
            'first_name' => 'Test',
            'last_name' => 'Buyer',
            'address1' => '123 Main St',
            'city' => 'Berlin',
            'country' => 'DE',
            'zip' => '10115',
        ],
    ]);
    $checkout = $checkoutService->setShippingMethod($checkout, $rate->id);
    $checkout = $checkoutService->selectPaymentMethod($checkout, $paymentMethod);

    return $checkoutService->completeCheckout($checkout, [
        'card_number' => '4242424242424242',
    ]);
}

// -- Tests --

it('creates order from completed checkout', function () {
    $order = completeCheckoutForOrder($this->store, $this->cartService, $this->checkoutService);

    expect($order)->toBeInstanceOf(Order::class);
    expect($order->store_id)->toBe($this->store->id);
    expect($order->status)->toBe(OrderStatus::Paid);
    expect($order->financial_status)->toBe(FinancialStatus::Paid);
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Unfulfilled);
    expect($order->email)->toBe('buyer@example.com');
});

it('generates sequential order numbers', function () {
    $order1 = completeCheckoutForOrder($this->store, $this->cartService, $this->checkoutService);
    $order2 = completeCheckoutForOrder($this->store, $this->cartService, $this->checkoutService);

    expect((int) $order2->order_number)->toBe((int) $order1->order_number + 1);
});

it('creates order lines with snapshots', function () {
    $variant = createOrderVariant($this->store, 3500);
    $order = completeCheckoutForOrder($this->store, $this->cartService, $this->checkoutService, [
        'variant' => $variant,
        'quantity' => 2,
    ]);

    $order->load('lines');
    expect($order->lines)->toHaveCount(1);

    $line = $order->lines->first();
    expect($line->title_snapshot)->not->toBeEmpty();
    expect($line->sku_snapshot)->toBe($variant->sku);
    expect($line->quantity)->toBe(2);
    expect($line->unit_price_amount)->toBe(3500);
});

it('commits inventory on order creation for credit card', function () {
    $variant = createOrderVariant($this->store, 2500, 10);
    $order = completeCheckoutForOrder($this->store, $this->cartService, $this->checkoutService, [
        'variant' => $variant,
        'quantity' => 3,
    ]);

    $variant->refresh()->load('inventoryItem');
    // Inventory was reserved (3) then committed: on_hand goes from 10 to 7, reserved back to 0
    expect($variant->inventoryItem->quantity_on_hand)->toBe(7);
    expect($variant->inventoryItem->quantity_reserved)->toBe(0);
});

it('marks cart as converted', function () {
    $variant = createOrderVariant($this->store);
    $rate = setupShippingAndTax($this->store);

    $cart = $this->cartService->create($this->store);
    $this->cartService->addLine($cart, $variant->id, 1);

    $checkout = Checkout::create([
        'store_id' => $this->store->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::Started,
    ]);
    $checkout = $this->checkoutService->setAddress($checkout, [
        'email' => 'test@example.com',
        'shipping_address' => ['country' => 'DE'],
    ]);
    $checkout = $this->checkoutService->setShippingMethod($checkout, $rate->id);
    $checkout = $this->checkoutService->selectPaymentMethod($checkout, PaymentMethod::CreditCard);
    $this->checkoutService->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    expect($cart->fresh()->status)->toBe(CartStatus::Converted);
});

it('dispatches OrderCreated event', function () {
    Event::fake([OrderCreated::class]);

    completeCheckoutForOrder($this->store, $this->cartService, $this->checkoutService);

    Event::assertDispatched(OrderCreated::class);
});

it('preserves order data when product is archived', function () {
    $variant = createOrderVariant($this->store, 4999);
    $product = $variant->product;
    $order = completeCheckoutForOrder($this->store, $this->cartService, $this->checkoutService, [
        'variant' => $variant,
    ]);

    // Archive the product
    $product->update(['status' => ProductStatus::Draft]);

    // Order data should still be intact
    $order->refresh()->load('lines');
    $line = $order->lines->first();
    expect($line->title_snapshot)->not->toBeEmpty();
    expect($line->unit_price_amount)->toBe(4999);
});

it('links order to customer when authenticated', function () {
    $customer = Customer::factory()->create(['store_id' => $this->store->id]);
    $order = completeCheckoutForOrder($this->store, $this->cartService, $this->checkoutService, [
        'customer' => $customer,
    ]);

    expect($order->customer_id)->toBe($customer->id);
});

it('sets email from checkout', function () {
    $order = completeCheckoutForOrder($this->store, $this->cartService, $this->checkoutService);

    expect($order->email)->toBe('buyer@example.com');
});
