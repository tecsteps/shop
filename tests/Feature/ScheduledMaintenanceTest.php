<?php

use App\Jobs\CancelUnpaidBankTransferOrders;
use App\Jobs\CleanupAbandonedCarts;
use App\Jobs\ExpireAbandonedCheckouts;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Order;
use App\Models\StoreSettings;
use App\Services\CheckoutService;
use App\Services\OrderService;

it('expires an idle checkout and releases its reserved stock', function () {
    $store = shopStore();
    $product = shopProduct($store, stock: 8);
    $product['inventory']->update(['quantity_reserved' => 2]);
    $cart = Cart::factory()->create(['store_id' => $store->id]);
    $cart->lines()->create([
        'variant_id' => $product['variant']->id,
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 5000,
        'line_discount_amount' => 0,
        'line_total_amount' => 5000,
    ]);
    $checkout = Checkout::query()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'status' => 'payment_selected',
        'payment_method' => 'credit_card',
        'expires_at' => now()->subMinute(),
    ]);

    (new ExpireAbandonedCheckouts)->handle(app(CheckoutService::class));

    expect($checkout->refresh()->status)->toBe('expired')
        ->and($product['inventory']->refresh()->quantity_reserved)->toBe(0);
});

it('abandons stale carts and expires their active checkouts', function () {
    $store = shopStore();
    $product = shopProduct($store);
    $product['inventory']->update(['quantity_reserved' => 1]);
    $cart = Cart::factory()->create(['store_id' => $store->id]);
    $cart->lines()->create([
        'variant_id' => $product['variant']->id,
        'quantity' => 1,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 2500,
        'line_discount_amount' => 0,
        'line_total_amount' => 2500,
    ]);
    DB::table('carts')->where('id', $cart->id)->update(['updated_at' => now()->subDays(15)]);
    $checkout = Checkout::query()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'status' => 'payment_selected',
        'payment_method' => 'credit_card',
        'expires_at' => now()->addHour(),
    ]);

    (new CleanupAbandonedCarts)->handle(app(CheckoutService::class));

    expect($cart->refresh()->status)->toBe('abandoned')
        ->and($checkout->refresh()->status)->toBe('expired')
        ->and($product['inventory']->refresh()->quantity_reserved)->toBe(0);
});

it('cancels overdue bank transfers using each store configured timeout', function () {
    $store = shopStore();
    StoreSettings::query()->create(['store_id' => $store->id, 'settings_json' => ['bank_transfer_cancel_days' => 3]]);
    $product = shopProduct($store, stock: 12);
    $product['inventory']->update(['quantity_reserved' => 2]);
    $order = Order::query()->create([
        'store_id' => $store->id,
        'order_number' => '#OLD-1001',
        'payment_method' => 'bank_transfer',
        'status' => 'pending',
        'financial_status' => 'pending',
        'currency' => 'EUR',
        'total_amount' => 5000,
        'placed_at' => now()->subDays(4),
    ]);
    $line = $order->lines()->create([
        'variant_id' => $product['variant']->id,
        'product_id' => $product['product']->id,
        'title_snapshot' => $product['product']->title,
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'total_amount' => 5000,
    ]);
    $payment = $order->payments()->create([
        'method' => 'bank_transfer',
        'status' => 'pending',
        'amount' => 5000,
        'currency' => 'EUR',
    ]);

    (new CancelUnpaidBankTransferOrders)->handle(app(OrderService::class));

    expect($order->refresh()->status)->toBe('cancelled')
        ->and($order->financial_status)->toBe('voided')
        ->and($payment->refresh()->status)->toBe('failed')
        ->and($product['inventory']->refresh()->quantity_reserved)->toBe(0);
});
