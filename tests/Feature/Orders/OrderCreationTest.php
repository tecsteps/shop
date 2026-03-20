<?php

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\OrderService;
use App\Services\Payment\PaymentResult;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->orderService = app(OrderService::class);

    $this->product = Product::factory()->create([
        'store_id' => $this->store->id,
        'title' => 'Test Product',
        'status' => 'active',
    ]);
    $this->variant = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'price_amount' => 2500,
        'sku' => 'TEST-SKU-001',
        'requires_shipping' => true,
    ]);
    $this->inventory = InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $this->variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 2,
        'policy' => 'deny',
    ]);

    $this->customer = Customer::factory()->create(['store_id' => $this->store->id]);

    $this->cart = Cart::factory()->create(['store_id' => $this->store->id]);
    CartLine::factory()->create([
        'cart_id' => $this->cart->id,
        'variant_id' => $this->variant->id,
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 5000,
        'line_total_amount' => 5000,
    ]);

    $this->checkout = Checkout::factory()->paymentSelected()->create([
        'store_id' => $this->store->id,
        'cart_id' => $this->cart->id,
        'customer_id' => $this->customer->id,
        'email' => 'customer@example.com',
        'payment_method' => PaymentMethod::CreditCard,
        'totals_json' => [
            'subtotal' => 5000,
            'discount' => 0,
            'shipping' => 499,
            'tax_total' => 1045,
            'total' => 6544,
            'currency' => 'USD',
        ],
    ]);

    $this->successResult = new PaymentResult(
        success: true,
        status: 'captured',
        providerPaymentId: 'mock_test_123',
        rawResponse: ['provider' => 'mock'],
    );
});

it('creates an order from a completed checkout', function () {
    Event::fake();

    $order = $this->orderService->createFromCheckout($this->checkout, $this->successResult);

    expect($order)->toBeInstanceOf(Order::class)
        ->and($order->store_id)->toBe($this->store->id)
        ->and($order->customer_id)->toBe($this->customer->id)
        ->and($order->order_number)->toBe('#1001')
        ->and($order->status)->toBe(OrderStatus::Paid)
        ->and($order->financial_status)->toBe(FinancialStatus::Paid)
        ->and($order->fulfillment_status)->toBe(FulfillmentStatus::Unfulfilled)
        ->and($order->email)->toBe('customer@example.com')
        ->and($order->subtotal_amount)->toBe(5000)
        ->and($order->total_amount)->toBe(6544);

    Event::assertDispatched(OrderCreated::class);
});

it('creates order lines with snapshot data', function () {
    $order = $this->orderService->createFromCheckout($this->checkout, $this->successResult);

    $order->load('lines');

    expect($order->lines)->toHaveCount(1);

    $line = $order->lines->first();
    expect($line->title_snapshot)->toBe('Test Product')
        ->and($line->sku_snapshot)->toBe('TEST-SKU-001')
        ->and($line->quantity)->toBe(2)
        ->and($line->unit_price_amount)->toBe(2500);
});

it('creates a payment record linked to the order', function () {
    $order = $this->orderService->createFromCheckout($this->checkout, $this->successResult);

    $order->load('payments');

    expect($order->payments)->toHaveCount(1);

    $payment = $order->payments->first();
    expect($payment->provider)->toBe('mock')
        ->and($payment->method)->toBe(PaymentMethod::CreditCard)
        ->and($payment->status)->toBe(PaymentStatus::Captured)
        ->and($payment->amount)->toBe(6544)
        ->and($payment->provider_payment_id)->toBe('mock_test_123');
});

it('commits inventory for credit card payment', function () {
    $this->orderService->createFromCheckout($this->checkout, $this->successResult);

    $this->inventory->refresh();

    // Was: on_hand=10, reserved=2. After commit of qty 2: on_hand=8, reserved=0
    expect($this->inventory->quantity_on_hand)->toBe(8)
        ->and($this->inventory->quantity_reserved)->toBe(0);
});

it('converts the cart to converted status', function () {
    $this->orderService->createFromCheckout($this->checkout, $this->successResult);

    $this->cart->refresh();
    $this->checkout->refresh();

    expect($this->cart->status)->toBe(CartStatus::Converted)
        ->and($this->checkout->status)->toBe(CheckoutStatus::Completed);
});

it('creates pending order for bank transfer', function () {
    $this->checkout->update(['payment_method' => PaymentMethod::BankTransfer]);

    $pendingResult = new PaymentResult(
        success: true,
        status: 'pending',
        providerPaymentId: 'mock_bank_456',
        rawResponse: ['provider' => 'mock', 'method' => 'bank_transfer'],
    );

    $order = $this->orderService->createFromCheckout($this->checkout->fresh(), $pendingResult);

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($order->financial_status)->toBe(FinancialStatus::Pending);

    // Inventory should NOT be committed for bank transfer - stays reserved
    $this->inventory->refresh();
    expect($this->inventory->quantity_on_hand)->toBe(10)
        ->and($this->inventory->quantity_reserved)->toBe(2);
});

it('generates sequential order numbers starting at 1001', function () {
    $orderNumber = $this->orderService->generateOrderNumber($this->store);
    expect($orderNumber)->toBe('#1001');

    // Create an order to advance the counter
    Order::factory()->create([
        'store_id' => $this->store->id,
        'order_number' => '#1001',
    ]);

    $orderNumber = $this->orderService->generateOrderNumber($this->store);
    expect($orderNumber)->toBe('#1002');
});

it('cancels an unfulfilled order', function () {
    Event::fake();

    $order = $this->orderService->createFromCheckout($this->checkout, $this->successResult);

    $this->orderService->cancel($order, 'Customer requested cancellation');

    $order->refresh();
    $this->inventory->refresh();

    expect($order->status)->toBe(OrderStatus::Cancelled)
        ->and($this->inventory->quantity_on_hand)->toBe(10);

    Event::assertDispatched(OrderCancelled::class);
});

it('prevents cancellation of a fulfilled order', function () {
    $order = $this->orderService->createFromCheckout($this->checkout, $this->successResult);
    $order->update(['fulfillment_status' => FulfillmentStatus::Fulfilled]);

    expect(fn () => $this->orderService->cancel($order->fresh(), 'test'))
        ->toThrow(RuntimeException::class);
});
