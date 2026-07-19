<?php

namespace App\Listeners;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

/**
 * Writes order lifecycle events to the audit log channel (spec 06 §4.6 /
 * spec 05 §17). Registered explicitly in AppServiceProvider for the order
 * and fulfillment events that exist so far; mailables arrive in a later
 * phase.
 */
class WriteAuditLog
{
    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        $order = $this->orderOf($event);

        Log::channel('audit')->info('order.'.$this->eventName($event), array_filter([
            'event' => $event::class,
            'store_id' => $order?->store_id,
            'order_id' => $order?->id,
            'order_number' => $order?->order_number,
            'financial_status' => $order?->financial_status?->value,
            'status' => $order?->status?->value,
            'fulfillment_id' => $event->fulfillment->id ?? null,
            'refund_id' => $event->refund->id ?? null,
            'reason' => $event->reason ?? null,
        ], fn ($value): bool => $value !== null));
    }

    /**
     * Resolve the order the event relates to.
     */
    private function orderOf(object $event): ?Order
    {
        if (isset($event->order) && $event->order instanceof Order) {
            return $event->order;
        }

        if (isset($event->fulfillment)) {
            return $event->fulfillment->order;
        }

        return null;
    }

    /**
     * Map the event class to a short audit action name.
     */
    private function eventName(object $event): string
    {
        return match (class_basename($event)) {
            'OrderCreated' => 'created',
            'OrderPaid' => 'paid',
            'OrderCancelled' => 'cancelled',
            'OrderRefunded' => 'refunded',
            'FulfillmentShipped' => 'fulfillment_shipped',
            default => strtolower(class_basename($event)),
        };
    }
}
