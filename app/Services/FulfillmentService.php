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
    public function create(Order $order, array $lines, ?array $tracking = null): Fulfillment
    {
        $allowedStatuses = [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded];

        if (! in_array($order->financial_status, $allowedStatuses)) {
            throw new FulfillmentGuardException(
                'Fulfillment cannot be created until payment is confirmed.'
            );
        }

        return DB::transaction(function () use ($order, $lines, $tracking) {
            $order->load('lines.fulfillmentLines');

            // Validate quantities
            foreach ($lines as $orderLineId => $quantity) {
                $orderLine = $order->lines->firstWhere('id', $orderLineId);

                if (! $orderLine) {
                    throw new \RuntimeException("Order line {$orderLineId} not found.");
                }

                $fulfilledSoFar = $orderLine->fulfillmentLines->sum('quantity');
                $unfulfilled = $orderLine->quantity - $fulfilledSoFar;

                if ($quantity > $unfulfilled) {
                    throw new \RuntimeException(
                        "Requested quantity ({$quantity}) exceeds unfulfilled quantity ({$unfulfilled}) for order line {$orderLineId}."
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

            // Update order fulfillment status
            $this->updateOrderFulfillmentStatus($order);

            return $fulfillment;
        });
    }

    public function markAsShipped(Fulfillment $fulfillment, ?array $tracking = null): void
    {
        if ($fulfillment->status !== FulfillmentShipmentStatus::Pending) {
            throw new \RuntimeException('Fulfillment is not in pending status.');
        }

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
        if ($fulfillment->status !== FulfillmentShipmentStatus::Shipped) {
            throw new \RuntimeException('Fulfillment is not in shipped status.');
        }

        $fulfillment->update([
            'status' => FulfillmentShipmentStatus::Delivered,
        ]);
    }

    protected function updateOrderFulfillmentStatus(Order $order): void
    {
        $order->load('lines');
        $allFulfilled = true;

        foreach ($order->lines as $line) {
            $totalFulfilled = FulfillmentLine::where('order_line_id', $line->id)->sum('quantity');

            if ($totalFulfilled < $line->quantity) {
                $allFulfilled = false;
                break;
            }
        }

        if ($allFulfilled) {
            $order->update([
                'fulfillment_status' => FulfillmentStatus::Fulfilled,
                'status' => OrderStatus::Fulfilled,
            ]);

            OrderFulfilled::dispatch($order->fresh());
        } else {
            $order->update([
                'fulfillment_status' => FulfillmentStatus::Partial,
            ]);
        }
    }
}
