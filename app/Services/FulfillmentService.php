<?php

namespace App\Services;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Events\OrderFulfilled;
use App\Exceptions\FulfillmentGuardException;
use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class FulfillmentService
{
    /**
     * @param  array<int, array{order_line_id: int, quantity: int}>  $lines
     * @param  array{tracking_company?: string, tracking_number?: string, tracking_url?: string}|null  $tracking
     */
    public function create(Order $order, array $lines, ?array $tracking = null): Fulfillment
    {
        if (! in_array($order->financial_status, [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded])) {
            throw new FulfillmentGuardException(
                "Cannot fulfill order with financial status '{$order->financial_status->value}'."
            );
        }

        return DB::transaction(function () use ($order, $lines, $tracking): Fulfillment {
            $order->load('lines', 'fulfillments.lines');

            foreach ($lines as $lineData) {
                $orderLine = $order->lines->firstWhere('id', $lineData['order_line_id']);

                if (! $orderLine) {
                    throw new \InvalidArgumentException("Order line {$lineData['order_line_id']} not found.");
                }

                $alreadyFulfilled = FulfillmentLine::whereHas('fulfillment', function ($q) use ($order): void {
                    $q->where('order_id', $order->id);
                })->where('order_line_id', $lineData['order_line_id'])
                    ->sum('quantity');

                $remaining = $orderLine->quantity - $alreadyFulfilled;

                if ($lineData['quantity'] > $remaining) {
                    throw new \InvalidArgumentException(
                        "Cannot fulfill {$lineData['quantity']} units for order line {$lineData['order_line_id']}. Only {$remaining} remaining."
                    );
                }
            }

            $fulfillment = Fulfillment::create([
                'order_id' => $order->id,
                'status' => FulfillmentShipmentStatus::Pending,
                'tracking_company' => $tracking['tracking_company'] ?? null,
                'tracking_number' => $tracking['tracking_number'] ?? null,
                'tracking_url' => $tracking['tracking_url'] ?? null,
            ]);

            foreach ($lines as $lineData) {
                FulfillmentLine::create([
                    'fulfillment_id' => $fulfillment->id,
                    'order_line_id' => $lineData['order_line_id'],
                    'quantity' => $lineData['quantity'],
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
        ]);
    }

    private function updateOrderFulfillmentStatus(Order $order): void
    {
        $order->load('lines', 'fulfillments.lines');

        $allFulfilled = true;
        $anyFulfilled = false;

        foreach ($order->lines as $orderLine) {
            $fulfilledQty = 0;

            foreach ($order->fulfillments as $fulfillment) {
                foreach ($fulfillment->lines as $fLine) {
                    if ($fLine->order_line_id === $orderLine->id) {
                        $fulfilledQty += $fLine->quantity;
                    }
                }
            }

            if ($fulfilledQty > 0) {
                $anyFulfilled = true;
            }

            if ($fulfilledQty < $orderLine->quantity) {
                $allFulfilled = false;
            }
        }

        if ($allFulfilled) {
            $order->update(['fulfillment_status' => FulfillmentStatus::Fulfilled]);
            OrderFulfilled::dispatch($order);
        } elseif ($anyFulfilled) {
            $order->update(['fulfillment_status' => FulfillmentStatus::Partial]);
        }
    }
}
