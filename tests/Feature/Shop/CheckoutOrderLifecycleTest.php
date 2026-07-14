<?php

use App\Enums\PaymentMethod;
use App\Events\CheckoutExpired;
use App\Events\OrderCreated;
use App\Events\OrderPaid;
use App\Events\OrderRefunded;
use App\Exceptions\DomainException;
use App\Exceptions\FulfillmentGuardException;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Exceptions\PaymentFailedException;
use App\Exceptions\ShippingUnavailableException;
use App\Jobs\CancelUnpaidBankTransferOrders;
use App\Jobs\ExpireAbandonedCheckouts;
use App\Models\Discount;
use App\Models\Fulfillment;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\FulfillmentService;
use App\Services\OrderService;
use App\Services\RefundService;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

describe('checkout state machine', function () {
    it('rejects an empty cart and invalid state jumps', function () {
        $store = createStoreContext()['store'];
        $empty = app(CartService::class)->create($store);

        expect(fn () => app(CheckoutService::class)->create($empty))
            ->toThrow(InvalidCheckoutTransitionException::class);

        $sku = makeSellableVariant($store);
        ['cart' => $cart] = makeCartWithLine($store, $sku['variant']);
        $started = app(CheckoutService::class)->create($cart);

        expect(fn () => app(CheckoutService::class)->completeCheckout($started))
            ->toThrow(InvalidCheckoutTransitionException::class);
    });

    it('validates address fields and remains started on failure', function () {
        $store = createStoreContext()['store'];
        $sku = makeSellableVariant($store);
        ['cart' => $cart] = makeCartWithLine($store, $sku['variant']);
        $checkout = app(CheckoutService::class)->create($cart);

        try {
            app(CheckoutService::class)->setAddress($checkout, [
                'email' => 'buyer@example.test',
                'shipping_address' => [
                    'first_name' => 'Ada', 'last_name' => 'Lovelace', 'address1' => 'Street 1',
                    'country_code' => 'DE', 'postal_code' => '10115',
                ],
            ]);
            $this->fail('An address without a city was accepted.');
        } catch (ValidationException $exception) {
            expect($exception->errors())->toHaveKey('city')
                ->and($checkout->refresh()->status->value)->toBe('started');
        }
    });

    it('moves through address shipping and payment while reserving stock', function () {
        $flow = checkoutReadyForPayment();

        expect($flow['checkout']->status->value)->toBe('payment_selected')
            ->and($flow['checkout']->shipping_method_id)->toBe($flow['rate']->id)
            ->and($flow['checkout']->payment_method)->toBe(PaymentMethod::CreditCard)
            ->and($flow['checkout']->expires_at->isFuture())->toBeTrue()
            ->and($flow['variant']->inventoryItem->refresh()->quantity_reserved)->toBe(2)
            ->and($flow['checkout']->totals_json)->toMatchArray([
                'subtotal' => 5000,
                'shipping' => 499,
                'total' => 5499,
                'currency' => $flow['store']->default_currency,
            ]);
    });

    it('rejects a shipping rate from a nonmatching zone', function () {
        $store = createStoreContext()['store'];
        $sku = makeSellableVariant($store);
        ['cart' => $cart] = makeCartWithLine($store, $sku['variant']);
        $us = ShippingZone::factory()->for($store)->create(['countries_json' => ['US']]);
        $usRate = ShippingRate::factory()->for($us, 'zone')->create();
        $checkout = app(CheckoutService::class)->create($cart);
        $checkout = app(CheckoutService::class)->setAddress($checkout, validCheckoutAddress('DE'));

        app(CheckoutService::class)->setShippingMethod($checkout, $usRate->id);
    })->throws(ShippingUnavailableException::class);

    it('expires selected checkouts idempotently and releases reservations', function () {
        Event::fake([CheckoutExpired::class]);
        $flow = checkoutReadyForPayment();
        $flow['checkout']->update(['expires_at' => now()->subMinute()]);

        (new ExpireAbandonedCheckouts)->handle(app(CheckoutService::class));

        expect($flow['checkout']->refresh()->status->value)->toBe('expired')
            ->and($flow['variant']->inventoryItem->refresh()->quantity_reserved)->toBe(0);
        Event::assertDispatched(CheckoutExpired::class, 1);

        app(CheckoutService::class)->expireCheckout($flow['checkout']);
        Event::assertDispatched(CheckoutExpired::class, 1);
    });
});

describe('payment outcomes and atomic order creation', function () {
    it('captures a credit card order with snapshots and commits inventory', function () {
        Event::fake([OrderCreated::class, OrderPaid::class]);
        $flow = checkoutReadyForPayment();

        $order = app(CheckoutService::class)->completeCheckout($flow['checkout'], ['card_number' => '4242 4242 4242 4242']);

        expect($order->status->value)->toBe('paid')
            ->and($order->financial_status->value)->toBe('paid')
            ->and($order->payment_method)->toBe(PaymentMethod::CreditCard)
            ->and($order->total_amount)->toBe(5499)
            ->and($order->email)->toBe('buyer@example.test')
            ->and($order->lines)->toHaveCount(1)
            ->and($order->lines->first()->title_snapshot)->toContain('Physical Guide')
            ->and($order->lines->first()->sku_snapshot)->toBe('PHYSICAL-GUIDE')
            ->and($order->payments->first()->status->value)->toBe('captured')
            ->and($order->payments->first()->method)->toBe(PaymentMethod::CreditCard)
            ->and($order->payments->first()->provider_payment_id)->toStartWith('mock_')
            ->and($flow['variant']->inventoryItem->refresh()->quantity_on_hand)->toBe(8)
            ->and($flow['variant']->inventoryItem->quantity_reserved)->toBe(0)
            ->and($flow['cart']->refresh()->status->value)->toBe('converted')
            ->and($flow['checkout']->refresh()->status->value)->toBe('completed');

        Event::assertDispatched(OrderCreated::class, fn ($event): bool => $event->order->is($order));
        Event::assertDispatched(OrderPaid::class, fn ($event): bool => $event->order->is($order));
    });

    it('encrypts the mock PSP snapshot at rest', function () {
        $flow = checkoutReadyForPayment();
        $order = app(CheckoutService::class)->completeCheckout($flow['checkout'], ['card_number' => '4242424242424242']);
        $payment = $order->payments->first();
        $rawDatabaseValue = Payment::query()->whereKey($payment->id)->toBase()->value('raw_json_encrypted');

        expect($rawDatabaseValue)->not->toContain('credit_card')
            ->and($payment->raw_json_encrypted)->toMatchArray(['provider' => 'mock', 'method' => 'credit_card']);
    });

    it('is idempotent when a payment completion is submitted twice', function () {
        $flow = checkoutReadyForPayment();
        $first = app(CheckoutService::class)->completeCheckout($flow['checkout'], ['card_number' => '4242424242424242']);
        $second = app(CheckoutService::class)->completeCheckout($flow['checkout']->refresh(), ['card_number' => '4242424242424242']);

        expect($second->id)->toBe($first->id)
            ->and(Order::withoutGlobalScopes()->where('store_id', $flow['store']->id)->count())->toBe(1)
            ->and(Payment::query()->where('order_id', $first->id)->count())->toBe(1);
    });

    it('releases inventory and creates no order for each declined card', function (string $number, string $code) {
        $flow = checkoutReadyForPayment();

        try {
            app(CheckoutService::class)->completeCheckout($flow['checkout'], ['card_number' => $number]);
            $this->fail('A rejected mock card created an order.');
        } catch (PaymentFailedException $exception) {
            expect($exception->errorCode)->toBe($code)
                ->and($flow['variant']->inventoryItem->refresh()->quantity_reserved)->toBe(0)
                ->and($flow['checkout']->refresh()->status->value)->toBe('shipping_selected')
                ->and($flow['checkout']->payment_method)->toBeNull()
                ->and(Order::withoutGlobalScopes()->where('store_id', $flow['store']->id)->count())->toBe(0);
        }
    })->with([
        'declined' => ['4000 0000 0000 0002', 'card_declined'],
        'insufficient funds' => ['4000 0000 0000 9995', 'insufficient_funds'],
    ]);

    it('captures PayPal through the same paid-order path', function () {
        $flow = checkoutReadyForPayment(PaymentMethod::Paypal);
        $order = app(CheckoutService::class)->completeCheckout($flow['checkout']);

        expect($order->payment_method)->toBe(PaymentMethod::Paypal)
            ->and($order->financial_status->value)->toBe('paid')
            ->and($order->payments->first()->status->value)->toBe('captured')
            ->and($flow['variant']->inventoryItem->refresh()->quantity_on_hand)->toBe(8);
    });

    it('numbers orders sequentially and independently per store', function () {
        $firstFlow = checkoutReadyForPayment(PaymentMethod::CreditCard, digital: true, quantity: 1);
        $first = app(CheckoutService::class)->completeCheckout($firstFlow['checkout'], ['card_number' => '4242424242424242']);

        bindStore($firstFlow['store']);
        $sku = makeSellableVariant($firstFlow['store'], variantAttributes: ['requires_shipping' => false, 'weight_g' => 0]);
        ['cart' => $cart] = makeCartWithLine($firstFlow['store'], $sku['variant']);
        $service = app(CheckoutService::class);
        $checkout = $service->create($cart);
        $checkout = $service->setAddress($checkout, validCheckoutAddress());
        $checkout = $service->setShippingMethod($checkout, null);
        $checkout = $service->selectPaymentMethod($checkout, PaymentMethod::CreditCard);
        $second = $service->completeCheckout($checkout, ['card_number' => '4242424242424242']);

        $otherFlow = checkoutReadyForPayment(PaymentMethod::CreditCard, digital: true, quantity: 1);
        $other = app(CheckoutService::class)->completeCheckout($otherFlow['checkout'], ['card_number' => '4242424242424242']);

        expect([$first->order_number, $second->order_number])->toBe(['#1001', '#1002'])
            ->and($other->order_number)->toBe('#1001');
    });

    it('links guest orders to a per-store customer and increments discount use once', function () {
        $flow = checkoutReadyForPayment();
        Discount::factory()->for($flow['store'])->create(['code' => 'ORDER10', 'usage_count' => 5]);
        $checkout = $flow['checkout'];
        // Applying a code happens before payment selection, so return safely to shipping-selected for this fixture.
        $flow['variant']->inventoryItem->update(['quantity_reserved' => 0]);
        $checkout->update(['status' => 'shipping_selected', 'payment_method' => null]);
        $checkout = app(CheckoutService::class)->applyDiscount($checkout, 'ORDER10');
        $checkout = app(CheckoutService::class)->selectPaymentMethod($checkout, PaymentMethod::CreditCard);

        $order = app(CheckoutService::class)->completeCheckout($checkout, ['card_number' => '4242424242424242']);
        $same = app(CheckoutService::class)->completeCheckout($checkout->refresh(), ['card_number' => '4242424242424242']);

        expect($order->customer)->not->toBeNull()
            ->and($order->customer->email)->toBe('buyer@example.test')
            ->and($same->id)->toBe($order->id)
            ->and(Discount::query()->where('code', 'ORDER10')->value('usage_count'))->toBe(6);
    });

    it('auto-fulfills an all-digital paid order', function () {
        $flow = checkoutReadyForPayment(PaymentMethod::CreditCard, digital: true);
        $order = app(CheckoutService::class)->completeCheckout($flow['checkout'], ['card_number' => '4242424242424242']);

        expect($order->refresh()->status->value)->toBe('fulfilled')
            ->and($order->fulfillment_status->value)->toBe('fulfilled')
            ->and($order->fulfillments)->toHaveCount(1)
            ->and($order->fulfillments->first()->status->value)->toBe('delivered')
            ->and($order->fulfillments->first()->lines->first()->quantity)->toBe(2);
    });
});

describe('bank transfers and unpaid cancellation', function () {
    it('keeps stock reserved until an admin confirms bank transfer', function () {
        Event::fake([OrderPaid::class]);
        $flow = checkoutReadyForPayment(PaymentMethod::BankTransfer);
        $order = app(CheckoutService::class)->completeCheckout($flow['checkout']);

        expect($order->status->value)->toBe('pending')
            ->and($order->financial_status->value)->toBe('pending')
            ->and($order->payments->first()->status->value)->toBe('pending')
            ->and($flow['variant']->inventoryItem->refresh()->quantity_on_hand)->toBe(10)
            ->and($flow['variant']->inventoryItem->quantity_reserved)->toBe(2);

        app(OrderService::class)->confirmBankTransfer($order);

        expect($order->refresh()->status->value)->toBe('paid')
            ->and($order->financial_status->value)->toBe('paid')
            ->and($order->payments()->first()->status->value)->toBe('captured')
            ->and($flow['variant']->inventoryItem->refresh()->quantity_on_hand)->toBe(8)
            ->and($flow['variant']->inventoryItem->quantity_reserved)->toBe(0);
        Event::assertDispatched(OrderPaid::class, 1);
    });

    it('rejects confirmation for non-bank and already confirmed orders', function () {
        $card = paidOrderFixture()['order'];
        expect(fn () => app(OrderService::class)->confirmBankTransfer($card))->toThrow(DomainException::class);

        $flow = checkoutReadyForPayment(PaymentMethod::BankTransfer);
        $bank = app(CheckoutService::class)->completeCheckout($flow['checkout']);
        app(OrderService::class)->confirmBankTransfer($bank);

        expect(fn () => app(OrderService::class)->confirmBankTransfer($bank->refresh()))->toThrow(DomainException::class);
    });

    it('cancels only overdue pending transfers and releases their inventory', function () {
        $oldFlow = checkoutReadyForPayment(PaymentMethod::BankTransfer);
        $old = app(CheckoutService::class)->completeCheckout($oldFlow['checkout']);
        $old->update(['placed_at' => now()->subDays(8)]);

        $recentFlow = checkoutReadyForPayment(PaymentMethod::BankTransfer);
        $recent = app(CheckoutService::class)->completeCheckout($recentFlow['checkout']);
        $recent->update(['placed_at' => now()->subDays(2)]);

        (new CancelUnpaidBankTransferOrders)->handle(app(OrderService::class));

        expect($old->refresh()->status->value)->toBe('cancelled')
            ->and($old->financial_status->value)->toBe('voided')
            ->and($old->payments()->first()->status->value)->toBe('failed')
            ->and($oldFlow['variant']->inventoryItem->refresh()->quantity_reserved)->toBe(0)
            ->and($recent->refresh()->status->value)->toBe('pending')
            ->and($recentFlow['variant']->inventoryItem->refresh()->quantity_reserved)->toBe(2);
    });

    it('auto-fulfills digital stock when bank payment is confirmed', function () {
        $flow = checkoutReadyForPayment(PaymentMethod::BankTransfer, digital: true);
        $order = app(CheckoutService::class)->completeCheckout($flow['checkout']);

        app(OrderService::class)->confirmBankTransfer($order);

        expect($order->refresh()->status->value)->toBe('fulfilled')
            ->and($order->fulfillment_status->value)->toBe('fulfilled')
            ->and($order->fulfillments()->first()->status->value)->toBe('delivered');
    });
});

describe('refunds and fulfillment', function () {
    it('processes partial then full refunds and records reasons', function () {
        Event::fake([OrderRefunded::class]);
        $fixture = paidOrderFixture();
        $service = app(RefundService::class);

        $partial = $service->create($fixture['order'], $fixture['payment'], 2000, 'Customer requested');
        expect($partial->status->value)->toBe('processed')
            ->and($partial->reason)->toBe('Customer requested')
            ->and($fixture['order']->refresh()->financial_status->value)->toBe('partially_refunded');

        $full = $service->create($fixture['order'], $fixture['payment'], 3000, 'Remaining balance');
        expect($full->status->value)->toBe('processed')
            ->and($fixture['order']->refresh()->financial_status->value)->toBe('refunded')
            ->and($fixture['order']->status->value)->toBe('refunded')
            ->and($fixture['payment']->refresh()->status->value)->toBe('refunded');
        Event::assertDispatched(OrderRefunded::class, 2);
    });

    it('rejects over-refunds and conditionally restocks line quantities', function () {
        $fixture = paidOrderFixture();
        $service = app(RefundService::class);

        expect(fn () => $service->create($fixture['order'], $fixture['payment'], 6000))
            ->toThrow(DomainException::class);

        $before = $fixture['variant']->inventoryItem->quantity_on_hand;
        $service->create($fixture['order'], $fixture['payment'], 2000, restock: true, lines: [$fixture['line']->id => 1]);
        expect($fixture['variant']->inventoryItem->refresh()->quantity_on_hand)->toBe($before + 1);

        $service->create($fixture['order'], $fixture['payment'], 1000, restock: false, lines: [$fixture['line']->id => 1]);
        expect($fixture['variant']->inventoryItem->refresh()->quantity_on_hand)->toBe($before + 1);
    });

    it('guards fulfillment by financial status', function (string $financialStatus, bool $allowed) {
        $fixture = paidOrderFixture();
        $fixture['order']->update(['financial_status' => $financialStatus]);
        $action = fn () => app(FulfillmentService::class)->create($fixture['order']->refresh(), [$fixture['line']->id => 1]);

        if ($allowed) {
            expect($action())->toBeInstanceOf(Fulfillment::class);
        } else {
            expect($action)->toThrow(FulfillmentGuardException::class);
        }
    })->with([
        'pending blocked' => ['pending', false],
        'authorized blocked' => ['authorized', false],
        'paid allowed' => ['paid', true],
        'partially refunded allowed' => ['partially_refunded', true],
        'refunded blocked' => ['refunded', false],
        'voided blocked' => ['voided', false],
    ]);

    it('tracks partial and complete fulfillment without over-shipping', function () {
        $fixture = paidOrderFixture(quantity: 3);
        $service = app(FulfillmentService::class);

        $first = $service->create($fixture['order'], [$fixture['line']->id => 1]);
        expect($fixture['order']->refresh()->fulfillment_status->value)->toBe('partial')
            ->and($first->lines->first()->quantity)->toBe(1);

        expect(fn () => $service->create($fixture['order'], [$fixture['line']->id => 3]))
            ->toThrow(ValidationException::class);

        $second = $service->create($fixture['order'], [$fixture['line']->id => 2]);
        expect($fixture['order']->refresh()->fulfillment_status->value)->toBe('fulfilled')
            ->and($fixture['order']->status->value)->toBe('fulfilled')
            ->and($second->lines->first()->quantity)->toBe(2);
    });

    it('moves fulfillment through shipped and delivered with tracking', function () {
        $fixture = paidOrderFixture();
        $service = app(FulfillmentService::class);
        $fulfillment = $service->create($fixture['order'], [$fixture['line']->id => 2]);

        $service->markAsShipped($fulfillment, [
            'tracking_company' => 'DHL',
            'tracking_number' => '123456',
            'tracking_url' => 'https://tracking.example/123456',
        ]);
        expect($fulfillment->refresh()->status->value)->toBe('shipped')
            ->and($fulfillment->tracking_company)->toBe('DHL')
            ->and($fulfillment->tracking_number)->toBe('123456')
            ->and($fulfillment->shipped_at)->not->toBeNull();

        $service->markAsDelivered($fulfillment);
        expect($fulfillment->refresh()->status->value)->toBe('delivered')
            ->and(fn () => $service->markAsDelivered($fulfillment))->toThrow(FulfillmentGuardException::class);
    });
});
