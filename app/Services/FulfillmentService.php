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
use App\Models\Store;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class FulfillmentService
{
    /**
     * @param  array<int, int>  $lines  Map of order_line_id to quantity
     * @param  array{tracking_company?: string, tracking_number?: string, tracking_url?: string}  $trackingData
     */
    public function createFulfillment(Order $order, array $lines, array $trackingData = []): Fulfillment
    {
        $this->guardFulfillment($order);

        $order->load('lines', 'fulfillments.lines');

        foreach ($lines as $lineId => $qty) {
            $orderLine = $order->lines->firstWhere('id', $lineId);

            if (! $orderLine) {
                throw new InvalidArgumentException("Order line {$lineId} not found.");
            }

            $fulfilledSoFar = $order->fulfillments->flatMap->lines
                ->where('order_line_id', $lineId)
                ->sum('quantity');

            $unfulfilled = $orderLine->quantity - $fulfilledSoFar;

            if ($qty > $unfulfilled) {
                throw new InvalidArgumentException(
                    "Cannot fulfill {$qty} of line {$lineId}. Only {$unfulfilled} remaining."
                );
            }
        }

        $fulfillment = $order->fulfillments()->create([
            'status' => FulfillmentShipmentStatus::Pending,
            'tracking_company' => $trackingData['tracking_company'] ?? null,
            'tracking_number' => $trackingData['tracking_number'] ?? null,
            'tracking_url' => $trackingData['tracking_url'] ?? null,
        ]);

        foreach ($lines as $lineId => $qty) {
            $fulfillment->lines()->create([
                'order_line_id' => $lineId,
                'quantity' => $qty,
            ]);
        }

        $this->updateOrderFulfillmentStatus($order);

        try {
            $store = Store::find($order->store_id);
            app(WebhookService::class)->dispatch($store, 'fulfillments/create', $fulfillment->toArray());
        } catch (\Throwable $e) {
            Log::warning('Webhook dispatch failed for fulfillments/create', ['error' => $e->getMessage()]);
        }

        return $fulfillment;
    }

    public function markAsShipped(Fulfillment $fulfillment): Fulfillment
    {
        $fulfillment->update([
            'status' => FulfillmentShipmentStatus::Shipped,
            'shipped_at' => now(),
        ]);

        return $fulfillment->fresh();
    }

    public function markAsDelivered(Fulfillment $fulfillment): Fulfillment
    {
        $fulfillment->update([
            'status' => FulfillmentShipmentStatus::Delivered,
            'delivered_at' => now(),
        ]);

        return $fulfillment->fresh();
    }

    private function guardFulfillment(Order $order): void
    {
        $allowed = [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded];

        if (! in_array($order->financial_status, $allowed)) {
            throw new FulfillmentGuardException(
                'Fulfillment cannot be created until payment is confirmed.'
            );
        }
    }

    private function updateOrderFulfillmentStatus(Order $order): void
    {
        $order->load('lines', 'fulfillments.lines');

        $allFulfilled = true;

        foreach ($order->lines as $orderLine) {
            $totalFulfilled = $order->fulfillments->flatMap->lines
                ->where('order_line_id', $orderLine->id)
                ->sum('quantity');

            if ($totalFulfilled < $orderLine->quantity) {
                $allFulfilled = false;

                break;
            }
        }

        if ($allFulfilled) {
            $order->update([
                'fulfillment_status' => FulfillmentStatus::Fulfilled,
                'status' => OrderStatus::Fulfilled,
            ]);

            OrderFulfilled::dispatch($order);
        } else {
            $hasSomeFulfillment = $order->fulfillments->isNotEmpty();

            if ($hasSomeFulfillment) {
                $order->update([
                    'fulfillment_status' => FulfillmentStatus::Partial,
                ]);
            }
        }
    }
}
