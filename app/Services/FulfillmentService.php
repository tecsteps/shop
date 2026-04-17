<?php

namespace App\Services;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Events\FulfillmentCreated;
use App\Events\FulfillmentShipped;
use App\Events\OrderFulfilled;
use App\Exceptions\FulfillmentGuardException;
use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FulfillmentService
{
    /**
     * @param  array<int, int>  $lines  Map of order_line_id => quantity
     * @param  array{tracking_company?: string|null, tracking_number?: string|null, tracking_url?: string|null}  $trackingData
     */
    public function create(Order $order, array $lines, array $trackingData = []): Fulfillment
    {
        $this->guardFinancialStatus($order);

        return DB::transaction(function () use ($order, $lines, $trackingData) {
            // Validate each line
            $order->load('lines.fulfillmentLines');

            foreach ($lines as $orderLineId => $quantity) {
                $orderLine = $order->lines->firstWhere('id', $orderLineId);

                if (! $orderLine) {
                    throw new RuntimeException("Order line {$orderLineId} does not belong to this order.");
                }

                $fulfilledSoFar = $orderLine->fulfillmentLines->sum('quantity');
                $unfulfilled = $orderLine->quantity - $fulfilledSoFar;

                if ($quantity > $unfulfilled) {
                    throw new RuntimeException(
                        "Cannot fulfill {$quantity} units of order line {$orderLineId}. Only {$unfulfilled} remaining."
                    );
                }
            }

            $fulfillment = Fulfillment::create([
                'order_id' => $order->id,
                'status' => FulfillmentShipmentStatus::Pending,
                'tracking_company' => $trackingData['tracking_company'] ?? null,
                'tracking_number' => $trackingData['tracking_number'] ?? null,
                'tracking_url' => $trackingData['tracking_url'] ?? null,
                'created_at' => now()->toIso8601String(),
            ]);

            foreach ($lines as $orderLineId => $quantity) {
                FulfillmentLine::create([
                    'fulfillment_id' => $fulfillment->id,
                    'order_line_id' => $orderLineId,
                    'quantity' => $quantity,
                ]);
            }

            // Determine the new order fulfillment status
            $this->updateOrderFulfillmentStatus($order);

            FulfillmentCreated::dispatch($fulfillment);

            return $fulfillment;
        });
    }

    public function markAsShipped(Fulfillment $fulfillment, array $trackingData = []): void
    {
        if ($fulfillment->status !== FulfillmentShipmentStatus::Pending) {
            throw new RuntimeException('Only pending fulfillments can be marked as shipped.');
        }

        $fulfillment->update([
            'status' => FulfillmentShipmentStatus::Shipped,
            'tracking_company' => $trackingData['tracking_company'] ?? $fulfillment->tracking_company,
            'tracking_number' => $trackingData['tracking_number'] ?? $fulfillment->tracking_number,
            'tracking_url' => $trackingData['tracking_url'] ?? $fulfillment->tracking_url,
            'shipped_at' => now()->toIso8601String(),
        ]);

        FulfillmentShipped::dispatch($fulfillment);
    }

    public function markAsDelivered(Fulfillment $fulfillment): void
    {
        if ($fulfillment->status !== FulfillmentShipmentStatus::Shipped) {
            throw new RuntimeException('Only shipped fulfillments can be marked as delivered.');
        }

        $fulfillment->update([
            'status' => FulfillmentShipmentStatus::Delivered,
            'delivered_at' => now()->toIso8601String(),
        ]);
    }

    private function guardFinancialStatus(Order $order): void
    {
        $allowed = [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded];

        if (! in_array($order->financial_status, $allowed, true)) {
            throw new FulfillmentGuardException;
        }
    }

    private function updateOrderFulfillmentStatus(Order $order): void
    {
        $order->refresh();
        $order->load('lines');

        $allFulfilled = true;
        $anyFulfilled = false;

        foreach ($order->lines as $line) {
            $totalFulfilled = FulfillmentLine::where('order_line_id', $line->id)->sum('quantity');

            if ($totalFulfilled > 0) {
                $anyFulfilled = true;
            }

            if ($totalFulfilled < $line->quantity) {
                $allFulfilled = false;
            }
        }

        if ($allFulfilled) {
            $order->update([
                'fulfillment_status' => FulfillmentStatus::Fulfilled,
                'status' => OrderStatus::Fulfilled,
            ]);

            OrderFulfilled::dispatch($order);
        } elseif ($anyFulfilled) {
            $order->update([
                'fulfillment_status' => FulfillmentStatus::Partial,
            ]);
        }
    }
}
