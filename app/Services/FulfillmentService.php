<?php

namespace App\Services;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Events\OrderFulfilled;
use App\Exceptions\FulfillmentGuardException;
use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FulfillmentService
{
    /**
     * Create a new fulfillment for an order.
     *
     * @param  array<int, int>  $lines  Map of order_line_id => quantity
     * @param  array{tracking_company?: string|null, tracking_number?: string|null, tracking_url?: string|null}  $trackingData
     */
    public function create(Order $order, array $lines, array $trackingData = []): Fulfillment
    {
        // Guard: check financial status
        $this->guardFinancialStatus($order);

        return DB::transaction(function () use ($order, $lines, $trackingData) {
            // Validate line quantities
            foreach ($lines as $lineId => $requestedQty) {
                $orderLine = $order->lines()->find($lineId);

                if (! $orderLine) {
                    throw new InvalidArgumentException("Order line {$lineId} not found.");
                }

                $fulfilledSoFar = FulfillmentLine::whereHas('fulfillment', function ($query) use ($order) {
                    $query->where('order_id', $order->id);
                })->where('order_line_id', $lineId)->sum('quantity');

                $unfulfilled = $orderLine->quantity - $fulfilledSoFar;

                if ($requestedQty > $unfulfilled) {
                    throw new InvalidArgumentException(
                        "Requested quantity ({$requestedQty}) exceeds unfulfilled quantity ({$unfulfilled}) for order line {$lineId}."
                    );
                }
            }

            // Create fulfillment
            $fulfillment = Fulfillment::create([
                'order_id' => $order->id,
                'status' => FulfillmentShipmentStatus::Pending,
                'tracking_company' => $trackingData['tracking_company'] ?? null,
                'tracking_number' => $trackingData['tracking_number'] ?? null,
                'tracking_url' => $trackingData['tracking_url'] ?? null,
                'created_at' => now(),
            ]);

            // Create fulfillment lines
            foreach ($lines as $lineId => $qty) {
                FulfillmentLine::create([
                    'fulfillment_id' => $fulfillment->id,
                    'order_line_id' => $lineId,
                    'quantity' => $qty,
                ]);
            }

            // Update order fulfillment status
            $this->updateOrderFulfillmentStatus($order);

            return $fulfillment;
        });
    }

    /**
     * Mark a fulfillment as shipped.
     *
     * @param  array{tracking_company?: string|null, tracking_number?: string|null, tracking_url?: string|null}  $trackingData
     */
    public function markAsShipped(Fulfillment $fulfillment, array $trackingData = []): Fulfillment
    {
        if ($fulfillment->status !== FulfillmentShipmentStatus::Pending) {
            throw new InvalidArgumentException('Fulfillment must be in pending status to mark as shipped.');
        }

        $fulfillment->update([
            'status' => FulfillmentShipmentStatus::Shipped,
            'tracking_company' => $trackingData['tracking_company'] ?? $fulfillment->tracking_company,
            'tracking_number' => $trackingData['tracking_number'] ?? $fulfillment->tracking_number,
            'tracking_url' => $trackingData['tracking_url'] ?? $fulfillment->tracking_url,
            'shipped_at' => now(),
        ]);

        return $fulfillment->refresh();
    }

    /**
     * Mark a fulfillment as delivered.
     */
    public function markAsDelivered(Fulfillment $fulfillment): Fulfillment
    {
        if ($fulfillment->status !== FulfillmentShipmentStatus::Shipped) {
            throw new InvalidArgumentException('Fulfillment must be in shipped status to mark as delivered.');
        }

        $fulfillment->update([
            'status' => FulfillmentShipmentStatus::Delivered,
        ]);

        return $fulfillment->refresh();
    }

    /**
     * Guard: fulfillment requires paid or partially_refunded financial status.
     *
     * @throws FulfillmentGuardException
     */
    private function guardFinancialStatus(Order $order): void
    {
        $allowed = [
            FinancialStatus::Paid,
            FinancialStatus::PartiallyRefunded,
        ];

        if (! in_array($order->financial_status, $allowed)) {
            throw new FulfillmentGuardException($order->financial_status->value);
        }
    }

    /**
     * Update the order's fulfillment status based on current fulfillments.
     */
    private function updateOrderFulfillmentStatus(Order $order): void
    {
        $allFulfilled = true;
        $anyFulfilled = false;

        foreach ($order->lines as $orderLine) {
            $totalFulfilled = FulfillmentLine::whereHas('fulfillment', function ($query) use ($order) {
                $query->where('order_id', $order->id);
            })->where('order_line_id', $orderLine->id)->sum('quantity');

            if ($totalFulfilled > 0) {
                $anyFulfilled = true;
            }

            if ($totalFulfilled < $orderLine->quantity) {
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
