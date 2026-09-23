<?php

namespace App\Listeners;

use App\Events\FulfillmentShipped;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderRefunded;
use App\Mail\CustomerOrderNotification;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;

class SendCustomerOrderNotifications
{
    public function orderCreated(OrderCreated $event): void
    {
        $this->send($event->order, CustomerOrderNotification::ORDER_CONFIRMATION);
    }

    public function orderRefunded(OrderRefunded $event): void
    {
        if (! $event->notifyCustomer) {
            return;
        }

        $this->send($event->order, CustomerOrderNotification::REFUND, [
            'amount' => (int) $event->refund->amount,
            'reason' => $event->refund->reason,
        ]);
    }

    public function fulfillmentShipped(FulfillmentShipped $event): void
    {
        if (! $event->notifyCustomer) {
            return;
        }

        $fulfillment = $event->fulfillment;
        $order = $fulfillment->order;

        if (! $order instanceof Order) {
            return;
        }

        $this->send($order, CustomerOrderNotification::SHIPPED, [
            'tracking_company' => $fulfillment->tracking_company,
            'tracking_number' => $fulfillment->tracking_number,
            'tracking_url' => $fulfillment->tracking_url,
        ]);
    }

    public function orderCancelled(OrderCancelled $event): void
    {
        if (! $event->notifyCustomer) {
            return;
        }

        $this->send($event->order, CustomerOrderNotification::CANCELLED);
    }

    /** @param array<string, mixed> $details */
    private function send(Order $order, string $notificationType, array $details = []): void
    {
        if (blank($order->email)) {
            return;
        }

        Mail::to($order->email)->queue(
            (new CustomerOrderNotification($order, $notificationType, $details))->afterCommit(),
        );
    }
}
