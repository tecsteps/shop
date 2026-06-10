<?php

namespace App\Listeners;

use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderPaid;
use App\Events\OrderRefunded;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

/**
 * Writes key business lifecycle events to the JSON "structured" logging
 * channel (spec 09 Phase 11) so operational tooling can ingest them
 * without parsing free-form log text.
 */
class LogStructuredBusinessEvent
{
    public function handle(OrderCreated|OrderPaid|OrderCancelled|OrderRefunded $event): void
    {
        $eventType = match ($event::class) {
            OrderCreated::class => 'order.created',
            OrderPaid::class => 'order.paid',
            OrderCancelled::class => 'order.cancelled',
            OrderRefunded::class => 'order.refunded',
        };

        $context = $this->orderContext($event->order);

        if ($event instanceof OrderRefunded) {
            $context['refund_id'] = $event->refund->getKey();
            $context['refund_amount'] = $event->refund->amount;
        }

        Log::channel('structured')->info($eventType, $context);
    }

    /**
     * @return array<string, mixed>
     */
    protected function orderContext(Order $order): array
    {
        return [
            'event' => 'business',
            'order_id' => $order->getKey(),
            'order_number' => $order->order_number,
            'store_id' => $order->store_id,
            'customer_id' => $order->customer_id,
            'payment_method' => $order->payment_method?->value,
            'currency' => $order->currency,
            'total_amount' => $order->total_amount,
            'status' => $order->status?->value,
            'financial_status' => $order->financial_status?->value,
        ];
    }
}
