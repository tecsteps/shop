<?php

namespace App\Services;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Events\OrderFulfilled;
use App\Exceptions\FulfillmentGuardException;
use App\Models\Fulfillment;
use App\Models\Order;

class FulfillmentService
{
    /**
     * @param  array<int, int>  $lines  [order_line_id => quantity]
     * @param  array<string, mixed>|null  $tracking
     */
    public function create(Order $order, array $lines, ?array $tracking = null): Fulfillment
    {
        if (! in_array($order->financial_status, [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded])) {
            throw new FulfillmentGuardException;
        }

        $fulfillment = $order->fulfillments()->create([
            'tracking_company' => $tracking['company'] ?? null,
            'tracking_number' => $tracking['number'] ?? null,
            'tracking_url' => $tracking['url'] ?? null,
            'status' => FulfillmentShipmentStatus::Pending,
        ]);

        foreach ($lines as $orderLineId => $quantity) {
            $fulfillment->lines()->create([
                'order_line_id' => $orderLineId,
                'quantity' => $quantity,
            ]);
        }

        $this->updateOrderFulfillmentStatus($order);

        return $fulfillment;
    }

    /**
     * @param  array<string, mixed>|null  $tracking
     */
    public function markAsShipped(Fulfillment $fulfillment, ?array $tracking = null): void
    {
        $fulfillment->update([
            'status' => FulfillmentShipmentStatus::Shipped,
            'shipped_at' => now(),
            'tracking_company' => $tracking['company'] ?? $fulfillment->tracking_company,
            'tracking_number' => $tracking['number'] ?? $fulfillment->tracking_number,
            'tracking_url' => $tracking['url'] ?? $fulfillment->tracking_url,
        ]);
    }

    public function markAsDelivered(Fulfillment $fulfillment): void
    {
        $fulfillment->update([
            'status' => FulfillmentShipmentStatus::Delivered,
            'delivered_at' => now(),
        ]);

        $this->updateOrderFulfillmentStatus($fulfillment->order);
    }

    private function updateOrderFulfillmentStatus(Order $order): void
    {
        $order->refresh();
        $totalOrderedQty = $order->lines->sum('quantity');
        $totalFulfilledQty = $order->fulfillments()
            ->with('lines')
            ->get()
            ->flatMap->lines
            ->sum('quantity');

        if ($totalFulfilledQty >= $totalOrderedQty) {
            $order->update([
                'fulfillment_status' => FulfillmentStatus::Fulfilled,
                'status' => OrderStatus::Fulfilled,
            ]);
            event(new OrderFulfilled($order));
        } elseif ($totalFulfilledQty > 0) {
            $order->update(['fulfillment_status' => FulfillmentStatus::Partial]);
        }
    }
}
