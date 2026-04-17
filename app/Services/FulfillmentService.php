<?php

namespace App\Services;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Events\FulfillmentDelivered;
use App\Exceptions\FulfillmentGuardException;
use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class FulfillmentService
{
    /**
     * Create a fulfillment for specific order lines.
     *
     * @param  array<int, int>  $lines  Map of order_line_id => quantity
     * @param  array<string, string|null>  $trackingData  tracking_company, tracking_number, tracking_url
     */
    public function create(Order $order, array $lines, array $trackingData = []): Fulfillment
    {
        $this->guardFulfillment($order);

        $order->loadMissing(['lines', 'fulfillments.lines']);

        // Validate quantities
        foreach ($lines as $orderLineId => $quantity) {
            $orderLine = $order->lines->find($orderLineId);
            if (! $orderLine) {
                throw new \InvalidArgumentException("Order line {$orderLineId} not found.");
            }

            /** @var int $fulfilledSoFar */
            $fulfilledSoFar = FulfillmentLine::query()
                ->whereHas('fulfillment', fn ($q) => $q->where('order_id', $order->id))
                ->where('order_line_id', $orderLineId)
                ->sum('quantity');

            $unfulfilled = $orderLine->quantity - $fulfilledSoFar;

            if ($quantity > $unfulfilled) {
                throw new \InvalidArgumentException(
                    "Cannot fulfill {$quantity} of order line {$orderLineId}: only {$unfulfilled} remaining."
                );
            }
        }

        /** @var Fulfillment $result */
        $result = DB::transaction(function () use ($order, $lines, $trackingData) {
            /** @var Fulfillment $fulfillment */
            $fulfillment = Fulfillment::query()->create([
                'order_id' => $order->id,
                'status' => FulfillmentShipmentStatus::Pending,
                'tracking_company' => $trackingData['tracking_company'] ?? null,
                'tracking_number' => $trackingData['tracking_number'] ?? null,
                'tracking_url' => $trackingData['tracking_url'] ?? null,
                'created_at' => now(),
            ]);

            foreach ($lines as $orderLineId => $quantity) {
                FulfillmentLine::query()->create([
                    'fulfillment_id' => $fulfillment->id,
                    'order_line_id' => $orderLineId,
                    'quantity' => $quantity,
                ]);
            }

            $this->updateOrderFulfillmentStatus($order);

            return $fulfillment;
        });

        return $result;
    }

    /**
     * Mark a fulfillment as shipped.
     *
     * @param  array<string, string|null>  $trackingData
     */
    public function markAsShipped(Fulfillment $fulfillment, array $trackingData = []): Fulfillment
    {
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
        $fulfillment->update([
            'status' => FulfillmentShipmentStatus::Delivered,
        ]);

        FulfillmentDelivered::dispatch($fulfillment->refresh());

        return $fulfillment;
    }

    /**
     * Guard: ensure order is paid or partially refunded before fulfillment.
     *
     * @throws FulfillmentGuardException
     */
    private function guardFulfillment(Order $order): void
    {
        $allowedStatuses = [
            FinancialStatus::Paid,
            FinancialStatus::PartiallyRefunded,
        ];

        if (! in_array($order->financial_status, $allowedStatuses, true)) {
            throw new FulfillmentGuardException($order->financial_status);
        }
    }

    /**
     * Update the order's fulfillment_status based on fulfilled quantities.
     */
    private function updateOrderFulfillmentStatus(Order $order): void
    {
        $order->loadMissing('lines');

        $allFulfilled = true;
        $anyFulfilled = false;

        foreach ($order->lines as $line) {
            $totalFulfilled = FulfillmentLine::query()
                ->whereHas('fulfillment', fn ($q) => $q->where('order_id', $order->id))
                ->where('order_line_id', $line->id)
                ->sum('quantity');

            if ($totalFulfilled >= $line->quantity) {
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
        } elseif ($anyFulfilled) {
            $order->update([
                'fulfillment_status' => FulfillmentStatus::Partial,
            ]);
        }
    }
}
