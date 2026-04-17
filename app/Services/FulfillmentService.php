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

class FulfillmentService
{
    /**
     * @param  array<int, int>  $lines  Map of order_line_id => quantity
     * @param  array<string, mixed>|null  $tracking  Tracking data
     */
    public function create(Order $order, array $lines, ?array $tracking = null): Fulfillment
    {
        $this->guardFinancialStatus($order);

        return DB::transaction(function () use ($order, $lines, $tracking) {
            // Validate line quantities
            $order->load('lines.fulfillmentLines');

            foreach ($lines as $orderLineId => $requestedQty) {
                $orderLine = $order->lines->firstWhere('id', $orderLineId);

                if (! $orderLine) {
                    throw new \RuntimeException("Order line {$orderLineId} not found on this order.");
                }

                $fulfilledSoFar = FulfillmentLine::query()
                    ->where('order_line_id', $orderLineId)
                    ->sum('quantity');

                $unfulfilled = $orderLine->quantity - $fulfilledSoFar;

                if ($requestedQty > $unfulfilled) {
                    throw new \RuntimeException(
                        "Cannot fulfill {$requestedQty} units of line {$orderLineId}. Only {$unfulfilled} remain unfulfilled."
                    );
                }
            }

            // Create fulfillment
            $fulfillment = Fulfillment::query()->create([
                'order_id' => $order->id,
                'status' => FulfillmentShipmentStatus::Pending,
                'tracking_company' => $tracking['tracking_company'] ?? null,
                'tracking_number' => $tracking['tracking_number'] ?? null,
                'tracking_url' => $tracking['tracking_url'] ?? null,
            ]);

            // Create fulfillment lines
            foreach ($lines as $orderLineId => $quantity) {
                FulfillmentLine::query()->create([
                    'fulfillment_id' => $fulfillment->id,
                    'order_line_id' => $orderLineId,
                    'quantity' => $quantity,
                ]);
            }

            // Update order fulfillment status
            $this->updateOrderFulfillmentStatus($order);

            return $fulfillment;
        });
    }

    public function markAsShipped(Fulfillment $fulfillment, ?array $tracking = null): void
    {
        if ($fulfillment->status !== FulfillmentShipmentStatus::Pending) {
            throw new \RuntimeException('Only pending fulfillments can be marked as shipped.');
        }

        $fulfillment->update([
            'status' => FulfillmentShipmentStatus::Shipped,
            'shipped_at' => now(),
            'tracking_company' => $tracking['tracking_company'] ?? $fulfillment->tracking_company,
            'tracking_number' => $tracking['tracking_number'] ?? $fulfillment->tracking_number,
            'tracking_url' => $tracking['tracking_url'] ?? $fulfillment->tracking_url,
        ]);
    }

    public function markAsDelivered(Fulfillment $fulfillment): void
    {
        if ($fulfillment->status !== FulfillmentShipmentStatus::Shipped) {
            throw new \RuntimeException('Only shipped fulfillments can be marked as delivered.');
        }

        $fulfillment->update([
            'status' => FulfillmentShipmentStatus::Delivered,
        ]);
    }

    protected function guardFinancialStatus(Order $order): void
    {
        $allowed = [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded];

        if (! in_array($order->financial_status, $allowed)) {
            throw new FulfillmentGuardException(
                'Fulfillment cannot be created until payment is confirmed. '
                . "Current financial status: {$order->financial_status->value}"
            );
        }
    }

    protected function updateOrderFulfillmentStatus(Order $order): void
    {
        $order->load('lines');
        $allFulfilled = true;
        $anyFulfilled = false;

        foreach ($order->lines as $orderLine) {
            $totalFulfilled = FulfillmentLine::query()
                ->where('order_line_id', $orderLine->id)
                ->sum('quantity');

            if ($totalFulfilled >= $orderLine->quantity) {
                $anyFulfilled = true;
            } else {
                $allFulfilled = false;
                if ($totalFulfilled > 0) {
                    $anyFulfilled = true;
                }
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
