<?php

use App\Exceptions\PaymentFailedException;
use App\Services\CheckoutService;
use App\Services\OrderService;
use App\Services\Payments\MockPaymentProvider;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Capture everything written to the "structured" logging channel.
 *
 * @param  list<array{0: string, 1: string, 2: array<string, mixed>}>  $logged
 */
function fakeStructuredChannel(array &$logged): void
{
    $channel = Mockery::mock(LoggerInterface::class);

    foreach (['info', 'warning'] as $level) {
        $channel->shouldReceive($level)->andReturnUsing(
            function (string $event, array $context = []) use (&$logged, $level): void {
                $logged[] = [$level, $event, $context];
            },
        );
    }

    Log::shouldReceive('channel')->with('structured')->andReturn($channel);
    Log::shouldReceive('channel')->andReturn(Mockery::spy(LoggerInterface::class))->byDefault();
    Log::shouldReceive('info', 'warning', 'error', 'debug')->andReturnNull()->byDefault();
}

it('writes an order.created entry to the structured channel when a checkout completes', function () {
    ['store' => $store] = createStoreContext();

    $logged = [];
    fakeStructuredChannel($logged);

    $checkout = createPaymentSelectedCheckout($store);

    $order = app(CheckoutService::class)->completeCheckout($checkout, [
        'card_number' => MockPaymentProvider::CARD_SUCCESS,
    ]);

    expect(array_column($logged, 1))->toContain('order.created');

    [, , $context] = collect($logged)->first(fn (array $entry): bool => $entry[1] === 'order.created');

    expect($context['order_id'])->toBe($order->getKey())
        ->and($context['store_id'])->toBe($store->getKey())
        ->and($context['total_amount'])->toBe($order->total_amount);
});

it('writes an order.paid entry to the structured channel when a bank transfer is confirmed', function () {
    ['store' => $store] = createStoreContext();

    $logged = [];
    fakeStructuredChannel($logged);

    $checkout = createPaymentSelectedCheckout($store, paymentMethod: 'bank_transfer');
    $order = app(CheckoutService::class)->completeCheckout($checkout);

    app(OrderService::class)->confirmBankTransferPayment($order);

    expect(array_column($logged, 1))->toContain('order.paid');

    [, , $context] = collect($logged)->first(fn (array $entry): bool => $entry[1] === 'order.paid');

    expect($context['order_id'])->toBe($order->getKey())
        ->and($context['financial_status'])->toBe('paid');
});

it('writes a payment.failed entry to the structured channel when the charge is declined', function () {
    ['store' => $store] = createStoreContext();

    $logged = [];
    fakeStructuredChannel($logged);

    $checkout = createPaymentSelectedCheckout($store);

    expect(fn () => app(CheckoutService::class)->completeCheckout($checkout, [
        'card_number' => MockPaymentProvider::CARD_DECLINED,
    ]))->toThrow(PaymentFailedException::class);

    expect(array_column($logged, 1))->toContain('payment.failed');

    [, , $context] = collect($logged)->first(fn (array $entry): bool => $entry[1] === 'payment.failed');

    expect($context['checkout_id'])->toBe($checkout->getKey())
        ->and($context['error_code'])->toBe('card_declined');
});
