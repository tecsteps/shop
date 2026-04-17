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
        // Fulfillment guard
        if (! in_array($order->financial_status, [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded])) {
            throw new FulfillmentGuardException;
        }

        return DB::transaction(function () use ($order, $lines, $tracking) {
            $order->load('lines.fulfillmentLines');

            // Validate quantities
            foreach ($lines as $orderLineId => $quantity) {
                $orderLine = $order->lines->firstWhere('id', $orderLineId);
                if (! $orderLine) {
                    throw new \InvalidArgumentException("Order line {$orderLineId} not found.");
                }

                $fulfilledSoFar = $orderLine->fulfillmentLines->sum('quantity');
                $unfulfilled = $orderLine->quantity - $fulfilledSoFar;

                if ($quantity > $unfulfilled) {
                    throw new \InvalidArgumentException(
                        "Requested quantity ({$quantity}) exceeds unfulfilled quantity ({$unfulfilled}) for order line {$orderLineId}."
                    );
                }
            }

            // Create fulfillment
            $fulfillment = Fulfillment::create([
                'order_id' => $order->id,
                'status' => FulfillmentShipmentStatus::Pending,
                'tracking_company' => $tracking['tracking_company'] ?? null,
                'tracking_number' => $tracking['tracking_number'] ?? null,
                'tracking_url' => $tracking['tracking_url'] ?? null,
            ]);

            // Create fulfillment lines
            foreach ($lines as $orderLineId => $quantity) {
                FulfillmentLine::create([
                    'fulfillment_id' => $fulfillment->id,
                    'order_line_id' => $orderLineId,
                    'quantity' => $quantity,
                ]);
            }

            // Determine new fulfillment status
            $allFulfilled = true;
            $order->load('lines.fulfillmentLines');

            foreach ($order->lines as $orderLine) {
                $totalFulfilled = $orderLine->fulfillmentLines->sum('quantity');
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
                $order->update([
                    'fulfillment_status' => FulfillmentStatus::Partial,
                ]);
            }

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
            'shipped_at' => now(),
            'tracking_company' => $tracking['tracking_company'] ?? $fulfillment->tracking_company,
            'tracking_number' => $tracking['tracking_number'] ?? $fulfillment->tracking_number,
            'tracking_url' => $tracking['tracking_url'] ?? $fulfillment->tracking_url,
        ]);
    }

    public function markAsDelivered(Fulfillment $fulfillment): void
    {
        $fulfillment->update([
            'status' => FulfillmentShipmentStatus::Delivered,
            'delivered_at' => now(),
        ]);
    }
}
