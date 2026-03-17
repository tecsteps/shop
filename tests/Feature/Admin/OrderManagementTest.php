<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\StoreUserRole;
use App\Livewire\Admin\Orders\Index as OrdersIndex;
use App\Livewire\Admin\Orders\Show as OrderShow;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->store = $this->ctx['store'];
    $this->user = $this->ctx['user'];
    $this->session = ['store_id' => $this->store->id, 'current_store_id' => $this->store->id];
});

it('lists orders with status filter', function () {
    Order::factory()->count(3)->create([
        'store_id' => $this->store->id,
        'status' => OrderStatus::Paid,
    ]);
    Order::factory()->count(2)->cancelled()->create([
        'store_id' => $this->store->id,
    ]);

    session($this->session);

    $component = Livewire::actingAs($this->user)
        ->test(OrdersIndex::class);

    $orders = $component->viewData('orders');
    expect($orders->total())->toBe(5);

    $component->set('statusFilter', 'cancelled');

    $orders = $component->viewData('orders');
    expect($orders->total())->toBe(2);
});

it('shows order detail page with line items', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
    ]);

    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price_amount' => 2500]);

    OrderLine::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'title_snapshot' => $product->title,
        'sku_snapshot' => $variant->sku,
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'total_amount' => 5000,
    ]);

    session($this->session);

    $component = Livewire::actingAs($this->user)
        ->test(OrderShow::class, ['order' => $order]);

    $component->assertOk()
        ->assertSee($order->order_number)
        ->assertSee($product->title);
});

it('creates a fulfillment for a paid order', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'financial_status' => FinancialStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Unfulfilled,
    ]);

    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    $line = OrderLine::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'title_snapshot' => $product->title,
        'sku_snapshot' => 'SKU-1',
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'total_amount' => 5000,
    ]);

    session($this->session);

    $component = Livewire::actingAs($this->user)
        ->test(OrderShow::class, ['order' => $order]);

    $component->set('fulfillmentLines', [
        ['line_id' => $line->id, 'quantity' => 2, 'selected' => true],
    ])
        ->set('trackingNumber', 'TRACK123')
        ->set('trackingCompany', 'DHL')
        ->call('createFulfillment');

    $component->assertDispatched('toast');

    $order->refresh();
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled);
    expect($order->fulfillments)->toHaveCount(1);
    expect($order->fulfillments->first()->tracking_number)->toBe('TRACK123');
});

it('processes a refund on a paid order', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'financial_status' => FinancialStatus::Paid,
        'total_amount' => 5000,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'status' => PaymentStatus::Captured,
        'amount' => 5000,
    ]);

    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    OrderLine::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'title_snapshot' => $product->title,
        'sku_snapshot' => 'SKU-1',
        'quantity' => 1,
        'unit_price_amount' => 5000,
        'total_amount' => 5000,
    ]);

    session($this->session);

    $component = Livewire::actingAs($this->user)
        ->test(OrderShow::class, ['order' => $order]);

    $component->set('refundAmount', 25)
        ->set('refundReason', 'Customer request')
        ->call('createRefund');

    $component->assertDispatched('toast');

    $order->refresh();
    expect($order->financial_status)->toBe(FinancialStatus::PartiallyRefunded);
    expect($order->refunds)->toHaveCount(1);
    expect($order->refunds->first()->amount)->toBe(2500);
});

it('restricts order management for support role users', function () {
    $supportUser = User::factory()->create();
    $this->store->users()->attach($supportUser->id, ['role' => StoreUserRole::Support]);

    $order = Order::factory()->create([
        'store_id' => $this->store->id,
    ]);

    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    OrderLine::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'title_snapshot' => $product->title,
        'sku_snapshot' => 'SKU-1',
        'quantity' => 1,
        'unit_price_amount' => 2500,
        'total_amount' => 2500,
    ]);

    // Support can view orders
    session($this->session);

    $component = Livewire::actingAs($supportUser)
        ->test(OrderShow::class, ['order' => $order]);

    $component->assertOk()
        ->assertSee($order->order_number);
});
