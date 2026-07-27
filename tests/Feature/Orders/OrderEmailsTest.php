<?php

use App\Mail\OrderCancelledMail;
use App\Mail\OrderConfirmationMail;
use App\Mail\OrderRefundedMail;
use App\Mail\OrderShippedMail;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\FulfillmentService;
use App\Services\OrderService;
use App\Services\RefundService;
use Illuminate\Support\Facades\Mail;

/**
 * Complete a checkout for a variant (2500, 10 on hand) with a DE flat
 * rate (499) and return the created order (spec 05 §17 email side effects).
 *
 * @return array{0: Store, 1: ProductVariant, 2: Order}
 */
function orderMailCheckout(string $paymentMethod = 'credit_card'): array
{
    $store = test()->createStore();
    test()->bindStore($store);

    $product = Product::factory()->active()->create(['store_id' => $store->id]);
    $variant = ProductVariant::factory()->withInventory(10)->create([
        'product_id' => $product->id,
        'price_amount' => 2500,
    ]);

    $zone = ShippingZone::factory()->create(['store_id' => $store->id, 'countries_json' => ['DE']]);
    $rate = ShippingRate::factory()->flat(499)->create(['zone_id' => $zone->id]);

    $service = app(CheckoutService::class);
    $cart = app(CartService::class)->create($store);
    app(CartService::class)->addLine($cart, $variant->id, 2);

    $checkout = $service->createFromCart($cart->refresh(), 'customer@example.com');
    $checkout = $service->setAddress($checkout, [
        'shipping_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'city' => 'Berlin',
            'country' => 'DE',
            'country_code' => 'DE',
            'postal_code' => '10115',
        ],
    ]);
    $checkout = $service->setShippingMethod($checkout, $rate->id);
    $checkout = $service->selectPaymentMethod($checkout, $paymentMethod);

    $order = $service->completeCheckout($checkout, ['card_number' => '4242424242424242']);

    return [$store, $variant, $order];
}

test('creating an order sends a confirmation mail to the checkout email', function () {
    Mail::fake();

    [, , $order] = orderMailCheckout();

    Mail::assertSent(OrderConfirmationMail::class, fn (OrderConfirmationMail $mail): bool => $mail->hasTo('customer@example.com')
        && $mail->order->id === $order->id);
});

test('confirmation mail renders items, totals, addresses, and store branding', function () {
    [, , $order] = orderMailCheckout();

    $mail = new OrderConfirmationMail($order);

    $mail->assertSeeInHtml($order->order_number)
        ->assertSeeInHtml($order->store->name)
        ->assertSeeInHtml($order->lines->first()->title_snapshot)
        ->assertSeeInHtml('54.99 USD')
        ->assertSeeInHtml('Jane Doe')
        ->assertSeeInHtml('10115 Berlin')
        ->assertDontSeeInHtml('Mock Bank AG');
});

test('confirmation mail for bank transfer orders includes the transfer instructions', function () {
    [, , $order] = orderMailCheckout('bank_transfer');

    $mail = new OrderConfirmationMail($order);

    $mail->assertSeeInHtml('Bank Transfer Instructions')
        ->assertSeeInHtml('Mock Bank AG')
        ->assertSeeInHtml('DE89 3704 0044 0532 0130 00')
        ->assertSeeInHtml($order->order_number);
});

test('marking a fulfillment as shipped sends the shipped mail with tracking', function () {
    [, , $order] = orderMailCheckout();
    $line = $order->lines->first();

    Mail::fake();

    $fulfillment = app(FulfillmentService::class)->create($order, [$line->id => 1]);
    app(FulfillmentService::class)->markAsShipped($fulfillment, [
        'tracking_company' => 'DHL',
        'tracking_number' => '123456',
        'tracking_url' => 'https://dhl.example/track/123456',
    ]);

    Mail::assertSent(OrderShippedMail::class, fn (OrderShippedMail $mail): bool => $mail->hasTo('customer@example.com')
        && $mail->fulfillment->id === $fulfillment->id);

    $mail = new OrderShippedMail($order, $fulfillment->refresh());
    $mail->assertSeeInHtml('DHL')
        ->assertSeeInHtml('123456')
        ->assertSeeInHtml('https://dhl.example/track/123456')
        ->assertSeeInHtml($line->title_snapshot);
});

test('cancelling an order sends the cancellation mail with the reason', function () {
    [, , $order] = orderMailCheckout();

    Mail::fake();

    app(OrderService::class)->cancel($order, 'customer request');

    Mail::assertSent(OrderCancelledMail::class, fn (OrderCancelledMail $mail): bool => $mail->hasTo('customer@example.com')
        && $mail->reason === 'customer request');

    $mail = new OrderCancelledMail($order, 'customer request');
    $mail->assertSeeInHtml('customer request')
        ->assertSeeInHtml($order->order_number);
});

test('refunding an order sends the refund mail with amount and reason', function () {
    [, , $order] = orderMailCheckout();
    $payment = $order->payments->first();

    Mail::fake();

    $refund = app(RefundService::class)->create($order, $payment, 2000, 'damaged item');

    Mail::assertSent(OrderRefundedMail::class, fn (OrderRefundedMail $mail): bool => $mail->hasTo('customer@example.com')
        && $mail->refund->id === $refund->id);

    $mail = new OrderRefundedMail($order, $refund);
    $mail->assertSeeInHtml('20.00 USD')
        ->assertSeeInHtml('damaged item');
});

test('a failing mailer never breaks order creation', function () {
    $mailer = Mockery::mock(Illuminate\Contracts\Mail\Factory::class);
    $mailer->shouldReceive('to')->andThrow(new RuntimeException('SMTP unreachable'));
    Mail::swap($mailer);

    [, , $order] = orderMailCheckout();

    expect($order->exists)->toBeTrue()
        ->and($order->refresh()->status->value)->toBe('paid');
});
