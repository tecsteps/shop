<?php

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\FinancialStatus;
use App\Enums\InventoryPolicy;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\ProductStatus;
use App\Enums\ShippingRateType;
use App\Events\OrderCreated;
use App\Events\OrderPaid;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\OrderService;
use App\Services\Payments\MockPaymentProvider;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    $ctx = $this->createStoreContext();
    $this->store = $ctx['store'];
    $this->cartService = app(CartService::class);
    $this->checkoutService = app(CheckoutService::class);
    $this->orderService = app(OrderService::class);

    $product = Product::factory()->create(['store_id' => $this->store->id, 'status' => ProductStatus::Active]);
    $this->variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2000, 'requires_shipping' => true]);
    InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $this->variant->id,
        'quantity_on_hand' => 10,
        'policy' => InventoryPolicy::Deny,
    ]);

    $zone = ShippingZone::factory()->create(['store_id' => $this->store->id, 'countries_json' => ['US']]);
    $this->rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
        'type' => ShippingRateType::Flat,
        'config_json' => ['amount' => 799],
    ]);
});

function prepCheckout(mixed $ctx, string $paymentMethod = 'credit_card'): \App\Models\Checkout
{
    $cart = $ctx->cartService->create($ctx->store);
    $ctx->cartService->addLine($cart, $ctx->variant->id, 1);
    $checkout = $ctx->checkoutService->startFromCart($cart);
    $ctx->checkoutService->setAddress($checkout, [
        'email' => 'a@b.co',
        'shipping_address' => [
            'first_name' => 'A', 'last_name' => 'B',
            'address1' => '1', 'city' => 'NY',
            'country_code' => 'US', 'zip' => '10001',
        ],
    ]);
    $ctx->checkoutService->setShippingMethod($checkout->fresh(), $ctx->rate->id);
    $ctx->checkoutService->selectPaymentMethod($checkout->fresh(), $paymentMethod);

    return $checkout->fresh();
}

it('creates a paid order with committed inventory for credit_card', function (): void {
    Event::fake([OrderCreated::class, OrderPaid::class]);

    $checkout = prepCheckout($this);
    $order = $this->orderService->createFromCheckout($checkout, ['card_number' => MockPaymentProvider::CARD_SUCCESS]);

    expect($order->status)->toBe(OrderStatus::Paid);
    expect($order->financial_status)->toBe(FinancialStatus::Paid);
    expect($order->payment_method)->toBe(PaymentMethod::CreditCard);

    $item = $this->variant->fresh()->inventoryItem;
    expect($item->quantity_on_hand)->toBe(9);
    expect($item->quantity_reserved)->toBe(0);

    Event::assertDispatched(OrderCreated::class);
    Event::assertDispatched(OrderPaid::class);
});

it('produces sequential order numbers starting at 1001', function (): void {
    $first = $this->orderService->createFromCheckout(prepCheckout($this), ['card_number' => MockPaymentProvider::CARD_SUCCESS]);

    // Need new cart since previous was converted
    $second = $this->orderService->createFromCheckout(prepCheckout($this), ['card_number' => MockPaymentProvider::CARD_SUCCESS]);

    expect($first->order_number)->toBe('1001');
    expect($second->order_number)->toBe('1002');
});

it('marks cart as converted and checkout as completed', function (): void {
    $checkout = prepCheckout($this);
    $this->orderService->createFromCheckout($checkout, ['card_number' => MockPaymentProvider::CARD_SUCCESS]);

    expect($checkout->cart->fresh()->status)->toBe(CartStatus::Converted);
    expect($checkout->fresh()->status)->toBe(CheckoutStatus::Completed);
});

it('is idempotent when called twice for the same checkout', function (): void {
    $checkout = prepCheckout($this);
    $first = $this->orderService->createFromCheckout($checkout, ['card_number' => MockPaymentProvider::CARD_SUCCESS]);
    $second = $this->orderService->createFromCheckout($checkout->fresh(), ['card_number' => MockPaymentProvider::CARD_SUCCESS]);

    expect($first->id)->toBe($second->id);
    expect(Order::query()->count())->toBe(1);
});

it('releases reserved inventory on payment decline', function (): void {
    $checkout = prepCheckout($this);

    expect(fn () => $this->orderService->createFromCheckout($checkout, ['card_number' => MockPaymentProvider::CARD_DECLINE]))
        ->toThrow(\App\Exceptions\PaymentFailedException::class);

    $item = $this->variant->fresh()->inventoryItem;
    expect($item->quantity_on_hand)->toBe(10);
    expect($item->quantity_reserved)->toBe(0);
});

it('keeps inventory reserved for bank_transfer orders', function (): void {
    $checkout = prepCheckout($this, 'bank_transfer');
    $order = $this->orderService->createFromCheckout($checkout);

    expect($order->status)->toBe(OrderStatus::Pending);
    expect($order->financial_status)->toBe(FinancialStatus::Pending);

    $item = $this->variant->fresh()->inventoryItem;
    expect($item->quantity_on_hand)->toBe(10);
    expect($item->quantity_reserved)->toBe(1);
});

it('stores line snapshots on order creation', function (): void {
    $checkout = prepCheckout($this);
    $order = $this->orderService->createFromCheckout($checkout, ['card_number' => MockPaymentProvider::CARD_SUCCESS]);

    $line = $order->lines()->first();
    expect($line->title_snapshot)->not->toBeNull();
    expect($line->sku_snapshot)->not->toBeNull();
    expect($line->quantity)->toBe(1);
    expect($line->unit_price_amount)->toBe(2000);
});
