<?php

use App\Events\OrderCreated;
use App\Events\ProductCreated;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\TestHandler;
use Monolog\Level;

/**
 * Structured audit logging (spec 06 §4.6, spec 05 §17): order lifecycle,
 * product, and admin auth events land on the "audit" channel as
 * structured context.
 */
function auditTestHandler(): TestHandler
{
    $handler = new TestHandler(Level::Info);
    Log::channel('audit')->pushHandler($handler);

    return $handler;
}

test('audit log receives an OrderCreated entry', function () {
    $store = $this->createStore();
    $order = Order::factory()->paid()->withLines([
        ['quantity' => 1, 'unit_price_amount' => 1000],
    ])->create(['store_id' => $store->id]);

    $handler = auditTestHandler();

    OrderCreated::dispatch($order);

    expect($handler->hasRecordThatPasses(
        fn ($record): bool => $record->message === 'order.created'
            && $record->context['order_id'] === $order->id
            && $record->context['order_number'] === $order->order_number
            && $record->context['store_id'] === $store->id,
        Level::Info,
    ))->toBeTrue();
});

test('audit log receives product.created and product.updated entries', function () {
    $store = $this->createStore();
    $product = Product::factory()->create(['store_id' => $store->id]);

    $handler = auditTestHandler();

    ProductCreated::dispatch($product);
    \App\Events\ProductUpdated::dispatch($product);

    expect($handler->hasRecordThatPasses(
        fn ($record): bool => $record->message === 'product.created'
            && $record->context['product_id'] === $product->id
            && $record->context['handle'] === $product->handle,
        Level::Info,
    ))->toBeTrue()
        ->and($handler->hasRecordThatPasses(
            fn ($record): bool => $record->message === 'product.updated'
                && $record->context['product_id'] === $product->id,
            Level::Info,
        ))->toBeTrue();
});

test('audit log receives admin logins but ignores customer logins', function () {
    $user = User::factory()->create();

    $handler = auditTestHandler();

    event(new Login('web', $user, false));
    event(new Login('customer', $user, false));

    expect($handler->hasRecordThatPasses(
        fn ($record): bool => $record->message === 'auth.login'
            && $record->context['user_id'] === $user->id
            && $record->context['guard'] === 'web',
        Level::Info,
    ))->toBeTrue()
        ->and($handler->hasRecordThatPasses(
            fn ($record): bool => $record->message === 'auth.login'
                && $record->context['guard'] === 'customer',
            Level::Info,
        ))->toBeFalse();
});
