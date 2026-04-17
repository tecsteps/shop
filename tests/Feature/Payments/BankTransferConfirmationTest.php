<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderCancelled;
use App\Events\OrderPaid;
use App\Jobs\CancelUnpaidBankTransferOrders;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->paymentService = app(PaymentService::class);

    $this->product = Product::factory()->create(['store_id' => $this->store->id]);
    $this->variant = ProductVariant::factory()->create([
        'product_id' => $this->product->id,
        'price_amount' => 2500,
        'requires_shipping' => true,
    ]);
    $this->inventory = InventoryItem::factory()->create([
        'store_id' => $this->store->id,
        'variant_id' => $this->variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 2,
        'policy' => 'deny',
    ]);

    $customer = Customer::factory()->create(['store_id' => $this->store->id]);
    $this->order = Order::factory()->pending()->create([
        'store_id' => $this->store->id,
        'customer_id' => $customer->id,
        'total_amount' => 5000,
    ]);
    $this->orderLine = OrderLine::factory()->create([
        'order_id' => $this->order->id,
        'product_id' => $this->product->id,
        'variant_id' => $this->variant->id,
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'total_amount' => 5000,
    ]);
    $this->payment = Payment::factory()->pending()->create([
        'order_id' => $this->order->id,
        'amount' => 5000,
    ]);
});

it('confirms bank transfer payment and updates statuses', function () {
    Event::fake();

    $this->paymentService->confirmBankTransfer($this->order);

    $this->order->refresh();
    $this->payment->refresh();

    expect($this->order->financial_status)->toBe(FinancialStatus::Paid)
        ->and($this->order->status)->toBe(OrderStatus::Paid)
        ->and($this->payment->status)->toBe(PaymentStatus::Captured);

    Event::assertDispatched(OrderPaid::class);
});

it('commits inventory on bank transfer confirmation', function () {
    $this->paymentService->confirmBankTransfer($this->order);

    $this->inventory->refresh();

    expect($this->inventory->quantity_on_hand)->toBe(8)
        ->and($this->inventory->quantity_reserved)->toBe(0);
});

it('rejects confirmation if order is not bank transfer', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'payment_method' => PaymentMethod::CreditCard,
        'financial_status' => FinancialStatus::Paid,
    ]);

    expect(fn () => $this->paymentService->confirmBankTransfer($order))
        ->toThrow(RuntimeException::class, 'Order is not a bank transfer order.');
});

it('rejects confirmation if financial status is not pending', function () {
    $this->order->update([
        'financial_status' => FinancialStatus::Paid,
        'status' => OrderStatus::Paid,
    ]);

    expect(fn () => $this->paymentService->confirmBankTransfer($this->order->fresh()))
        ->toThrow(RuntimeException::class, 'Order financial status is not pending.');
});

it('auto-fulfills digital products on bank transfer confirmation', function () {
    // Make the variant digital
    $this->variant->update(['requires_shipping' => false]);

    $this->paymentService->confirmBankTransfer($this->order);

    $this->order->refresh();

    expect($this->order->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled)
        ->and($this->order->status)->toBe(OrderStatus::Fulfilled)
        ->and($this->order->fulfillments)->toHaveCount(1)
        ->and($this->order->fulfillments->first()->status)->toBe(FulfillmentShipmentStatus::Delivered);
});

it('cancels unpaid bank transfer orders after timeout', function () {
    Event::fake();

    // Set placed_at to 8 days ago (past the 7-day default)
    $this->order->update(['placed_at' => now()->subDays(8)->toIso8601String()]);

    $job = new CancelUnpaidBankTransferOrders;
    $job->handle(app(\App\Services\InventoryService::class));

    $this->order->refresh();
    $this->payment->refresh();
    $this->inventory->refresh();

    expect($this->order->status)->toBe(OrderStatus::Cancelled)
        ->and($this->order->financial_status)->toBe(FinancialStatus::Voided)
        ->and($this->payment->status)->toBe(PaymentStatus::Failed)
        ->and($this->inventory->quantity_reserved)->toBe(0)
        ->and($this->inventory->quantity_on_hand)->toBe(10);

    Event::assertDispatched(OrderCancelled::class);
});
