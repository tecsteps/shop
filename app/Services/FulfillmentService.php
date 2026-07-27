<?php

namespace App\Services;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentOrderStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\OrderStatus;
use App\Events\FulfillmentCreated;
use App\Events\FulfillmentDelivered;
use App\Events\FulfillmentShipped;
use App\Events\OrderFulfilled;
use App\Exceptions\FulfillmentGuardException;
use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Fulfillment creation and status management (spec 05 §11.5).
 *
 * Guard: a fulfillment can only be created when the order's financial status
 * is `paid` or `partially_refunded` — never ship before payment is confirmed.
 */
class FulfillmentService
{
    /**
     * Create a fulfillment for the given lines (order_line_id => quantity).
     *
     * @param  array<int|string, int>  $lines
     * @param  array{tracking_company?: string|null, tracking_number?: string|null, tracking_url?: string|null}|null  $tracking
     *
     * @throws FulfillmentGuardException|ValidationException
     */
    public function create(Order $order, array $lines, ?array $tracking = null): Fulfillment
    {
        if (! in_array($order->financial_status, [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded], true)) {
            throw FulfillmentGuardException::forFinancialStatus($order->financial_status->value);
        }

        return DB::transaction(function () use ($order, $lines, $tracking): Fulfillment {
            $order->loadMissing('lines');
            $fulfilledSoFar = $this->fulfilledQuantities($order);
            $orderLines = $order->lines->keyBy('id');

            $errors = [];

            foreach ($lines as $orderLineId => $quantity) {
                $orderLine = $orderLines->get((int) $orderLineId);
                $quantity = (int) $quantity;

                if ($orderLine === null) {
                    $errors["lines.{$orderLineId}"] = ['The order line does not belong to this order.'];

                    continue;
                }

                $unfulfilled = $orderLine->quantity - ($fulfilledSoFar[$orderLine->id] ?? 0);

                if ($quantity < 1 || $quantity > $unfulfilled) {
                    $errors["lines.{$orderLineId}"] = ["Cannot fulfill {$quantity} units; only {$unfulfilled} unfulfilled."];
                }
            }

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            $fulfillment = $order->fulfillments()->create([
                'status' => FulfillmentShipmentStatus::Pending,
                'tracking_company' => $tracking['tracking_company'] ?? null,
                'tracking_number' => $tracking['tracking_number'] ?? null,
                'tracking_url' => $tracking['tracking_url'] ?? null,
            ]);

            foreach ($lines as $orderLineId => $quantity) {
                $fulfillment->lines()->create([
                    'order_line_id' => (int) $orderLineId,
                    'quantity' => (int) $quantity,
                ]);
            }

            $this->recomputeOrderStatus($order->refresh());

            FulfillmentCreated::dispatch($fulfillment);

            return $fulfillment;
        });
    }

    /**
     * Auto-create a delivered fulfillment covering all lines of an
     * all-digital order (spec 05 §11.7). Skips pending/shipped and sets
     * shipped_at immediately. No-op for mixed or physical orders.
     */
    public function autoFulfillDigital(Order $order): ?Fulfillment
    {
        if (! $order->isDigital() || $order->fulfillment_status === FulfillmentOrderStatus::Fulfilled) {
            return null;
        }

        $order->loadMissing('lines');

        $fulfillment = $order->fulfillments()->create([
            'status' => FulfillmentShipmentStatus::Delivered,
            'shipped_at' => now(),
        ]);

        foreach ($order->lines as $line) {
            $fulfillment->lines()->create([
                'order_line_id' => $line->id,
                'quantity' => $line->quantity,
            ]);
        }

        $order->forceFill([
            'fulfillment_status' => FulfillmentOrderStatus::Fulfilled,
            'status' => OrderStatus::Fulfilled,
        ])->save();

        FulfillmentCreated::dispatch($fulfillment);
        OrderFulfilled::dispatch($order);

        return $fulfillment;
    }

    /**
     * Transition pending -> shipped: set tracking data and shipped_at.
     *
     * @param  array{tracking_company?: string|null, tracking_number?: string|null, tracking_url?: string|null}|null  $tracking
     */
    public function markAsShipped(Fulfillment $fulfillment, ?array $tracking = null): void
    {
        $fulfillment->forceFill([
            'status' => FulfillmentShipmentStatus::Shipped,
            'shipped_at' => $fulfillment->shipped_at ?? now(),
            'tracking_company' => $tracking['tracking_company'] ?? $fulfillment->tracking_company,
            'tracking_number' => $tracking['tracking_number'] ?? $fulfillment->tracking_number,
            'tracking_url' => $tracking['tracking_url'] ?? $fulfillment->tracking_url,
        ])->save();

        FulfillmentShipped::dispatch($fulfillment);
    }

    /**
     * Transition shipped -> delivered.
     */
    public function markAsDelivered(Fulfillment $fulfillment): void
    {
        $fulfillment->forceFill(['status' => FulfillmentShipmentStatus::Delivered])->save();

        FulfillmentDelivered::dispatch($fulfillment);
    }

    /**
     * Recompute the order's fulfillment_status (and overall status when fully
     * fulfilled) from all fulfillment lines.
     */
    private function recomputeOrderStatus(Order $order): void
    {
        $order->loadMissing('lines');
        $fulfilled = $this->fulfilledQuantities($order);

        $allFulfilled = true;
        $anyFulfilled = false;

        foreach ($order->lines as $line) {
            $quantity = (int) ($fulfilled[$line->id] ?? 0);

            if ($quantity > 0) {
                $anyFulfilled = true;
            }

            if ($quantity < $line->quantity) {
                $allFulfilled = false;
            }
        }

        if ($allFulfilled && $order->lines->isNotEmpty()) {
            $wasFulfilled = $order->fulfillment_status === FulfillmentOrderStatus::Fulfilled;

            $order->forceFill([
                'fulfillment_status' => FulfillmentOrderStatus::Fulfilled,
                'status' => OrderStatus::Fulfilled,
            ])->save();

            if (! $wasFulfilled) {
                OrderFulfilled::dispatch($order);
            }
        } elseif ($anyFulfilled) {
            $order->forceFill(['fulfillment_status' => FulfillmentOrderStatus::Partial])->save();
        }
    }

    /**
     * Fulfilled quantity per order line id, across all fulfillments.
     *
     * @return array<int, int>
     */
    private function fulfilledQuantities(Order $order): array
    {
        return FulfillmentLine::query()
            ->whereIn('fulfillment_id', $order->fulfillments()->pluck('id'))
            ->selectRaw('order_line_id, SUM(quantity) as total')
            ->groupBy('order_line_id')
            ->pluck('total', 'order_line_id')
            ->map(fn ($total): int => (int) $total)
            ->all();
    }
}
