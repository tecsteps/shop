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
     * @param  array{tracking_company?: string, tracking_number?: string, tracking_url?: string}|null  $tracking
     */
    public function create(Order $order, array $lines, ?array $tracking = null): Fulfillment
    {
        $this->guardFulfillment($order);

        return DB::transaction(function () use ($order, $lines, $tracking) {
            // Validate quantities
            $order->load('lines.fulfillmentLines');

            foreach ($lines as $orderLineId => $quantity) {
                $orderLine = $order->lines->firstWhere('id', $orderLineId);

                if (! $orderLine) {
                    throw new \InvalidArgumentException("Order line {$orderLineId} not found.");
                }

                $fulfilledSoFar = $orderLine->fulfillmentLines->sum('quantity');
                $unfulfilled = $orderLine->quantity - $fulfilledSoFar;

                if ($quantity > $unfulfilled) {
                    throw new \InvalidArgumentException(
                        "Cannot fulfill {$quantity} units of line {$orderLineId}. Only {$unfulfilled} remaining."
                    );
                }
            }

            $fulfillment = Fulfillment::create([
                'order_id' => $order->id,
                'status' => FulfillmentShipmentStatus::Pending,
                'tracking_company' => $tracking['tracking_company'] ?? null,
                'tracking_number' => $tracking['tracking_number'] ?? null,
                'tracking_url' => $tracking['tracking_url'] ?? null,
                'created_at' => now(),
            ]);

            foreach ($lines as $orderLineId => $quantity) {
                FulfillmentLine::create([
                    'fulfillment_id' => $fulfillment->id,
                    'order_line_id' => $orderLineId,
                    'quantity' => $quantity,
                ]);
            }

            $this->updateOrderFulfillmentStatus($order);

            return $fulfillment;
        });
    }

    /**
     * @param  array{tracking_company?: string, tracking_number?: string, tracking_url?: string}|null  $tracking
     */
    public function markAsShipped(Fulfillment $fulfillment, ?array $tracking = null): void
    {
        $fulfillment->update([
            'status' => FulfillmentShipmentStatus::Shipped,
            'tracking_company' => $tracking['tracking_company'] ?? $fulfillment->tracking_company,
            'tracking_number' => $tracking['tracking_number'] ?? $fulfillment->tracking_number,
            'tracking_url' => $tracking['tracking_url'] ?? $fulfillment->tracking_url,
            'shipped_at' => now(),
        ]);
    }

    public function markAsDelivered(Fulfillment $fulfillment): void
    {
        $fulfillment->update([
            'status' => FulfillmentShipmentStatus::Delivered,
            'delivered_at' => now(),
        ]);
    }

    private function guardFulfillment(Order $order): void
    {
        $allowed = [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded];

        if (! in_array($order->financial_status, $allowed)) {
            throw new FulfillmentGuardException;
        }
    }

    private function updateOrderFulfillmentStatus(Order $order): void
    {
        $order->load('lines.fulfillmentLines');

        $allFulfilled = true;
        $anyFulfilled = false;

        foreach ($order->lines as $line) {
            $totalFulfilled = $line->fulfillmentLines->sum('quantity');

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
