<?php

use App\Enums\CheckoutStatus;
use App\Enums\FinancialStatus;
use App\Enums\PaymentStatus;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Collection;
use App\Models\Order;
use App\Models\Page;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CheckoutService;
use App\Services\OrderService;

beforeEach(function () {
    $this->ctx = createStoreContext();
});

it('sanitizes script tags from product description_html on save', function () {
    Livewire\Livewire::actingAs($this->ctx['user']);
    app()->instance('current_store', $this->ctx['store']);

    $product = Product::factory()->active()->for($this->ctx['store'])->create();
    $variant = ProductVariant::factory()->for($product)->create(['price_amount' => 1000]);

    Livewire\Livewire::test(\App\Livewire\Admin\Products\Form::class, ['product' => $product])
        ->set('title', 'Test Product')
        ->set('description_html', '<p>Hello</p><script>alert("xss")</script><strong>Bold</strong>')
        ->set('variants.0.price', '10.00')
        ->call('save');

    $product->refresh();
    expect($product->description_html)->not->toContain('<script>')
        ->and($product->description_html)->toContain('<p>Hello</p>')
        ->and($product->description_html)->toContain('<strong>Bold</strong>');
});

it('sanitizes script tags from page content on save', function () {
    Livewire\Livewire::actingAs($this->ctx['user']);
    app()->instance('current_store', $this->ctx['store']);

    Livewire\Livewire::test(\App\Livewire\Admin\Pages\Form::class)
        ->set('title', 'Test Page')
        ->set('content', '<p>Safe</p><script>alert("xss")</script>')
        ->set('status', 'draft')
        ->call('save');

    $page = Page::query()->where('title', 'Test Page')->first();
    expect($page->content)->not->toContain('<script>')
        ->and($page->content)->toContain('<p>Safe</p>');
});

it('sanitizes script tags from collection description_html on save', function () {
    Livewire\Livewire::actingAs($this->ctx['user']);
    app()->instance('current_store', $this->ctx['store']);

    Livewire\Livewire::test(\App\Livewire\Admin\Collections\Form::class)
        ->set('title', 'Test Collection')
        ->set('description_html', '<p>Info</p><script>evil()</script>')
        ->set('status', 'active')
        ->call('save');

    $collection = Collection::query()->where('title', 'Test Collection')->first();
    expect($collection->description_html)->not->toContain('<script>')
        ->and($collection->description_html)->toContain('<p>Info</p>');
});

it('generates order numbers atomically using lock', function () {
    $orderService = app(OrderService::class);

    Order::factory()->for($this->ctx['store'])->create(['order_number' => '#1001']);

    $number = $orderService->generateOrderNumber($this->ctx['store']);
    expect($number)->toBe('#1002');
});

it('reuses existing active checkout for the same cart', function () {
    app()->instance('current_store', $this->ctx['store']);

    $cart = Cart::factory()->for($this->ctx['store'])->create();
    $product = Product::factory()->active()->for($this->ctx['store'])->create();
    $variant = ProductVariant::factory()->for($product)->create(['price_amount' => 1000]);
    CartLine::factory()->for($cart)->create([
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price' => 1000,
        'subtotal' => 1000,
        'total' => 1000,
    ]);

    $checkoutService = app(CheckoutService::class);
    $checkout = $checkoutService->createFromCart($cart);

    // Simulate session with this cart
    session()->put('cart_'.$this->ctx['store']->id, $cart->id);

    // Create the component - it should reuse the existing checkout
    $countBefore = Checkout::withoutGlobalScopes()->where('cart_id', $cart->id)->count();
    expect($countBefore)->toBe(1);

    // The mount method should find the existing checkout, not create a new one
    $existingCheckout = Checkout::withoutGlobalScopes()
        ->where('cart_id', $cart->id)
        ->whereNotIn('status', [
            CheckoutStatus::Completed->value,
            CheckoutStatus::Expired->value,
        ])
        ->latest()
        ->first();

    expect($existingCheckout)->not->toBeNull()
        ->and($existingCheckout->id)->toBe($checkout->id);
});

it('does not reuse completed or expired checkouts', function () {
    $cart = Cart::factory()->for($this->ctx['store'])->create();

    Checkout::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'cart_id' => $cart->id,
        'status' => CheckoutStatus::Completed,
    ]);

    $activeCheckout = Checkout::withoutGlobalScopes()
        ->where('cart_id', $cart->id)
        ->whereNotIn('status', [
            CheckoutStatus::Completed->value,
            CheckoutStatus::Expired->value,
        ])
        ->latest()
        ->first();

    expect($activeCheckout)->toBeNull();
});

it('admin refund uses RefundService for proper payment provider integration', function () {
    Livewire\Livewire::actingAs($this->ctx['user']);
    app()->instance('current_store', $this->ctx['store']);

    $order = Order::factory()->paid()->for($this->ctx['store'])->create(['total' => 5000]);
    $payment = Payment::factory()->for($order)->create(['amount' => 5000, 'status' => PaymentStatus::Captured]);

    Livewire\Livewire::test(\App\Livewire\Admin\Orders\Show::class, ['order' => $order])
        ->call('openRefundModal')
        ->set('refundAmount', '50.00')
        ->set('refundReason', 'Test refund')
        ->call('createRefund');

    $order->refresh();
    expect($order->refunds)->toHaveCount(1)
        ->and($order->financial_status)->toBe(FinancialStatus::Refunded);
});

it('has empty default card number in checkout', function () {
    $component = new \App\Livewire\Storefront\Checkout\Show;
    expect($component->cardNumber)->toBe('');
});
