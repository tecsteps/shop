<?php

use App\Events\FulfillmentShipped;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderRefunded;
use App\Mail\CustomerOrderNotification;
use App\Models\Fulfillment;
use App\Models\Order;
use App\Models\Refund;
use App\Models\Store;
use App\Models\User;
use App\Services\FulfillmentService;
use App\Services\RefundService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

function customerNotificationTestOrder(Store $store, string $number, string $email): Order
{
    $order = Order::query()->create([
        'store_id' => $store->getKey(),
        'order_number' => $number,
        'payment_method' => 'credit_card',
        'status' => 'paid',
        'financial_status' => 'paid',
        'fulfillment_status' => 'unfulfilled',
        'currency' => $store->default_currency,
        'subtotal_amount' => 5000,
        'total_amount' => 5000,
        'email' => $email,
        'placed_at' => now(),
    ]);
    $order->lines()->create([
        'title_snapshot' => 'Cotton shirt',
        'variant_title_snapshot' => 'Blue / Medium',
        'quantity' => 2,
        'unit_price_amount' => 2500,
        'total_amount' => 5000,
    ]);

    return $order;
}

function customerNotificationApiToken(User $user, Store $store): string
{
    $plainTextToken = 'shop_'.Str::random(64);
    $user->tokens()->create([
        'store_id' => $store->getKey(),
        'name' => 'customer-notifications-test',
        'token' => hash('sha256', $plainTextToken),
        'abilities' => ['write-orders'],
        'expires_at' => now()->addHour(),
    ]);

    return $plainTextToken;
}

function invokeCustomerOrderEventListeners(object $event): void
{
    foreach (app('events')->getListeners($event::class) as $listener) {
        $listener($event, [$event]);
    }
}

it('queues the registered customer emails for order, refund, shipment, and cancellation events', function () {
    $store = shopStore();
    $order = customerNotificationTestOrder($store, '#EMAIL-1001', 'customer@example.test');
    $refund = new Refund(['amount' => 1250, 'reason' => 'Returned item']);
    $fulfillment = new Fulfillment([
        'status' => 'shipped',
        'tracking_company' => 'DHL',
        'tracking_number' => 'TRACK-123',
        'tracking_url' => 'https://tracking.example.test/TRACK-123',
    ]);
    $fulfillment->setRelation('order', $order);
    Mail::fake();
    $bankTransferOrder = customerNotificationTestOrder($store, '#EMAIL-1007', 'bank-transfer@example.test');
    $bankTransferOrder->forceFill(['payment_method' => 'bank_transfer', 'status' => 'pending', 'financial_status' => 'pending'])->save();

    invokeCustomerOrderEventListeners(new OrderCreated($order));
    invokeCustomerOrderEventListeners(new OrderRefunded($order, $refund));
    invokeCustomerOrderEventListeners(new FulfillmentShipped($fulfillment));
    invokeCustomerOrderEventListeners(new OrderCancelled($order));
    invokeCustomerOrderEventListeners(new OrderCreated($bankTransferOrder));

    Mail::assertQueued(CustomerOrderNotification::class, function (CustomerOrderNotification $mail) use ($order): bool {
        if ($mail->notificationType !== CustomerOrderNotification::ORDER_CONFIRMATION
            || $mail->order->getKey() !== $order->getKey()) {
            return false;
        }

        $mail->assertSeeInHtml('Thank you for your order');
        $mail->assertSeeInHtml('Cotton shirt');

        return $mail->hasTo('customer@example.test');
    });
    Mail::assertQueued(CustomerOrderNotification::class, function (CustomerOrderNotification $mail): bool {
        if ($mail->notificationType !== CustomerOrderNotification::REFUND) {
            return false;
        }

        $mail->assertSeeInHtml('Returned item');

        return $mail->details === ['amount' => 1250, 'reason' => 'Returned item'];
    });
    Mail::assertQueued(CustomerOrderNotification::class, function (CustomerOrderNotification $mail): bool {
        if ($mail->notificationType !== CustomerOrderNotification::SHIPPED) {
            return false;
        }

        $mail->assertSeeInHtml('TRACK-123');

        return $mail->details['tracking_number'] === 'TRACK-123';
    });
    Mail::assertQueued(CustomerOrderNotification::class, function (CustomerOrderNotification $mail): bool {
        if ($mail->notificationType !== CustomerOrderNotification::CANCELLED) {
            return false;
        }

        $mail->assertSeeInHtml('Your order was cancelled');

        return true;
    });
    Mail::assertQueued(CustomerOrderNotification::class, function (CustomerOrderNotification $mail) use ($bankTransferOrder): bool {
        if ($mail->notificationType !== CustomerOrderNotification::ORDER_CONFIRMATION
            || $mail->order->getKey() !== $bankTransferOrder->getKey()) {
            return false;
        }

        $mail->assertSeeInHtml('DE89 3704 0044 0532 0130 00');

        return true;
    });
    Mail::assertQueuedCount(5);
});

it('suppresses refund and shipment email when the event disables customer notification', function () {
    $store = shopStore();
    $order = customerNotificationTestOrder($store, '#EMAIL-1002', 'customer@example.test');
    $refund = new Refund(['amount' => 1250, 'reason' => 'Returned item']);
    $fulfillment = new Fulfillment(['status' => 'shipped', 'tracking_number' => 'TRACK-456']);
    $fulfillment->setRelation('order', $order);
    Mail::fake();

    invokeCustomerOrderEventListeners(new OrderRefunded($order, $refund, false));
    invokeCustomerOrderEventListeners(new FulfillmentShipped($fulfillment, false));

    Mail::assertNothingOutgoing();
});

it('passes the default and explicit customer notification choice to order services', function () {
    $store = shopStore();
    $user = User::factory()->create();
    $user->stores()->attach($store->getKey(), ['role' => 'owner']);
    $token = customerNotificationApiToken($user, $store);
    $defaultRefundOrder = customerNotificationTestOrder($store, '#EMAIL-1003', 'default@example.test');
    $silentRefundOrder = customerNotificationTestOrder($store, '#EMAIL-1004', 'silent@example.test');
    $defaultFulfillmentOrder = customerNotificationTestOrder($store, '#EMAIL-1005', 'default-ship@example.test');
    $silentFulfillmentOrder = customerNotificationTestOrder($store, '#EMAIL-1006', 'silent-ship@example.test');
    $refund = new Refund(['id' => 1, 'order_id' => $defaultRefundOrder->getKey(), 'amount' => 1000, 'status' => 'processed']);
    $refundService = Mockery::mock(RefundService::class);
    $refundService->shouldReceive('refund')
        ->twice()
        ->withArgs(fn (Order $order, int $amount, ?string $reason, bool $restock, array $lineItems, bool $notifyCustomer): bool => $amount === 1000
            && $reason === null
            && ! $restock
            && $lineItems === []
            && $notifyCustomer === ($order->getKey() === $defaultRefundOrder->getKey()))
        ->andReturn($refund);
    $this->app->instance(RefundService::class, $refundService);

    $baseUrl = "/api/admin/v1/stores/{$store->getKey()}/orders";
    $this->withToken($token)->postJson("{$baseUrl}/{$defaultRefundOrder->getKey()}/refunds", ['amount' => 1000])->assertCreated();
    $this->withToken($token)->postJson("{$baseUrl}/{$silentRefundOrder->getKey()}/refunds", ['amount' => 1000, 'notify_customer' => false])->assertCreated();

    $defaultFulfillment = $defaultFulfillmentOrder->fulfillments()->create(['status' => 'shipped', 'shipped_at' => now(), 'created_at' => now()]);
    $defaultFulfillment->lines()->create(['order_line_id' => $defaultFulfillmentOrder->lines()->firstOrFail()->getKey(), 'quantity' => 1]);
    $silentFulfillment = $silentFulfillmentOrder->fulfillments()->create(['status' => 'shipped', 'shipped_at' => now(), 'created_at' => now()]);
    $silentFulfillment->lines()->create(['order_line_id' => $silentFulfillmentOrder->lines()->firstOrFail()->getKey(), 'quantity' => 1]);
    $fulfillmentService = Mockery::mock(FulfillmentService::class);
    $fulfillmentService->shouldReceive('create')->twice()->andReturn($defaultFulfillment, $silentFulfillment);
    $fulfillmentService->shouldReceive('markShipped')
        ->twice()
        ->withArgs(fn (Fulfillment $fulfillment, bool $notifyCustomer): bool => $notifyCustomer === ($fulfillment->getKey() === $defaultFulfillment->getKey()))
        ->andReturn($defaultFulfillment, $silentFulfillment);
    $this->app->instance(FulfillmentService::class, $fulfillmentService);

    $this->withToken($token)->postJson("{$baseUrl}/{$defaultFulfillmentOrder->getKey()}/fulfillments", [
        'line_items' => [['order_line_id' => $defaultFulfillmentOrder->lines()->firstOrFail()->getKey(), 'quantity' => 1]],
    ])->assertCreated();
    $this->withToken($token)->postJson("{$baseUrl}/{$silentFulfillmentOrder->getKey()}/fulfillments", [
        'line_items' => [['order_line_id' => $silentFulfillmentOrder->lines()->firstOrFail()->getKey(), 'quantity' => 1]],
        'notify_customer' => false,
    ])->assertCreated();
});
