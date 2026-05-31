<?php

namespace App\Services;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Events\FulfillmentCreated;
use App\Events\FulfillmentDelivered;
use App\Events\FulfillmentShipped;
use App\Events\OrderFulfilled;
use App\Exceptions\FulfillmentGuardException;
use App\Models\Fulfillment;
use App\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Creates and advances fulfillments (shipments) for an order.
 *
 * A fulfillment may only be created once payment is confirmed: the order's
 * financial status must be `paid` or `partially_refunded`, otherwise a
 * {@see FulfillmentGuardException} is thrown. Requested quantities may not exceed
 * the unfulfilled quantity of an order line. The order's fulfillment status is
 * recomputed (partial / fulfilled) after each change.
 */
class FulfillmentService
{
    /**
     * Create a fulfillment covering the given order-line quantities.
     *
     * @param  array<int, int>  $lines  order_line_id => quantity
     * @param  array{tracking_company?: ?string, tracking_number?: ?string, tracking_url?: ?string}|null  $tracking
     *
     * @throws FulfillmentGuardException
     */
    public function create(Order $order, array $lines, ?array $tracking = null): Fulfillment
    {
        $this->assertFulfillable($order);

        return DB::transaction(function () use ($order, $lines, $tracking): Fulfillment {
            $this->assertQuantities($order, $lines);

            $fulfillment = $order->fulfillments()->create([
                'status' => FulfillmentShipmentStatus::Pending->value,
                'tracking_company' => $tracking['tracking_company'] ?? null,
                'tracking_number' => $tracking['tracking_number'] ?? null,
                'tracking_url' => $tracking['tracking_url'] ?? null,
            ]);

            foreach ($lines as $orderLineId => $quantity) {
                if ($quantity > 0) {
                    $fulfillment->lines()->create([
                        'order_line_id' => $orderLineId,
                        'quantity' => $quantity,
                    ]);
                }
            }

            $this->syncOrderFulfillmentStatus($order);

            FulfillmentCreated::dispatch($fulfillment);

            return $fulfillment;
        });
    }

    /**
     * Mark a pending fulfillment as shipped, recording tracking details.
     *
     * @param  array{tracking_company?: ?string, tracking_number?: ?string, tracking_url?: ?string}|null  $tracking
     */
    public function markAsShipped(Fulfillment $fulfillment, ?array $tracking = null): void
    {
        $fulfillment->update([
            'status' => FulfillmentShipmentStatus::Shipped->value,
            'tracking_company' => $tracking['tracking_company'] ?? $fulfillment->tracking_company,
            'tracking_number' => $tracking['tracking_number'] ?? $fulfillment->tracking_number,
            'tracking_url' => $tracking['tracking_url'] ?? $fulfillment->tracking_url,
            'shipped_at' => Carbon::now(),
        ]);

        FulfillmentShipped::dispatch($fulfillment->refresh());
    }

    /**
     * Mark a shipped fulfillment as delivered.
     */
    public function markAsDelivered(Fulfillment $fulfillment): void
    {
        $fulfillment->update([
            'status' => FulfillmentShipmentStatus::Delivered->value,
            'delivered_at' => Carbon::now(),
        ]);

        FulfillmentDelivered::dispatch($fulfillment->refresh());
    }

    /**
     * Auto-create a delivered fulfillment covering every line when an order is
     * entirely digital (no line requires shipping). No-op for mixed or physical
     * orders, or when the order has no lines.
     */
    public function autoFulfillDigital(Order $order): ?Fulfillment
    {
        $order->loadMissing('lines.variant');

        if ($order->lines->isEmpty() || ! $this->isAllDigital($order)) {
            return null;
        }

        return DB::transaction(function () use ($order): Fulfillment {
            $fulfillment = $order->fulfillments()->create([
                'status' => FulfillmentShipmentStatus::Delivered->value,
                'shipped_at' => Carbon::now(),
                'delivered_at' => Carbon::now(),
            ]);

            foreach ($order->lines as $line) {
                $fulfillment->lines()->create([
                    'order_line_id' => $line->id,
                    'quantity' => $line->quantity,
                ]);
            }

            $this->syncOrderFulfillmentStatus($order);

            FulfillmentCreated::dispatch($fulfillment);
            FulfillmentDelivered::dispatch($fulfillment);

            return $fulfillment;
        });
    }

    /**
     * @throws FulfillmentGuardException
     */
    private function assertFulfillable(Order $order): void
    {
        if (! in_array($order->financial_status, [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded], true)) {
            throw new FulfillmentGuardException(
                'Fulfillment cannot be created until payment is confirmed.',
            );
        }
    }

    /**
     * @param  array<int, int>  $lines
     *
     * @throws FulfillmentGuardException
     */
    private function assertQuantities(Order $order, array $lines): void
    {
        $order->loadMissing('lines.fulfillmentLines');

        foreach ($lines as $orderLineId => $quantity) {
            $line = $order->lines->firstWhere('id', $orderLineId);

            if ($line === null) {
                throw new FulfillmentGuardException("Order line {$orderLineId} does not belong to this order.");
            }

            $unfulfilled = $line->quantity - $line->fulfilledQuantity();

            if ($quantity > $unfulfilled) {
                throw new FulfillmentGuardException(
                    "Cannot fulfill {$quantity} of order line {$orderLineId}: only {$unfulfilled} remain.",
                );
            }
        }
    }

    /**
     * Recompute the order's fulfillment status from its fulfillment lines.
     */
    private function syncOrderFulfillmentStatus(Order $order): void
    {
        $order->load('lines.fulfillmentLines');

        $anyFulfilled = false;
        $allFulfilled = true;

        foreach ($order->lines as $line) {
            $fulfilled = $line->fulfilledQuantity();

            if ($fulfilled > 0) {
                $anyFulfilled = true;
            }

            if ($fulfilled < $line->quantity) {
                $allFulfilled = false;
            }
        }

        if ($allFulfilled && $anyFulfilled) {
            $order->update([
                'fulfillment_status' => FulfillmentStatus::Fulfilled->value,
                'status' => OrderStatus::Fulfilled->value,
            ]);
            OrderFulfilled::dispatch($order->refresh());
        } elseif ($anyFulfilled) {
            $order->update(['fulfillment_status' => FulfillmentStatus::Partial->value]);
        }
    }

    private function isAllDigital(Order $order): bool
    {
        return $order->lines->every(
            fn ($line): bool => $line->variant !== null && ! $line->variant->requires_shipping,
        );
    }
}
