<?php

namespace App\Services;

use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Events\FulfillmentDelivered;
use App\Events\OrderFulfilled;
use App\Exceptions\FulfillmentGuardException;
use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use App\Models\Order;
use App\Models\OrderLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FulfillmentService
{
    /**
     * Create a fulfillment for specific order lines and quantities.
     *
     * @param  array<int, int>  $lines  Quantity to fulfill keyed by order line id
     * @param  array{tracking_company?: string|null, tracking_number?: string|null, tracking_url?: string|null}|null  $tracking
     *
     * @throws FulfillmentGuardException
     * @throws ValidationException
     */
    public function create(Order $order, array $lines, ?array $tracking = null): Fulfillment
    {
        $this->assertFulfillmentAllowed($order);

        $lines = array_filter($lines, fn (int $quantity): bool => $quantity > 0);

        if ($lines === []) {
            throw ValidationException::withMessages([
                'lines' => __('A fulfillment requires at least one line.'),
            ]);
        }

        $orderLines = $order->lines()->whereIn('id', array_keys($lines))->get()->keyBy('id');

        foreach ($lines as $orderLineId => $quantity) {
            $orderLine = $orderLines->get($orderLineId);

            if ($orderLine === null) {
                throw ValidationException::withMessages([
                    'lines' => __('Order line :id does not belong to this order.', ['id' => $orderLineId]),
                ]);
            }

            if ($quantity > $orderLine->unfulfilledQuantity()) {
                throw ValidationException::withMessages([
                    'lines' => __('Cannot fulfill more units than remain unfulfilled for ":title".', [
                        'title' => $orderLine->title_snapshot,
                    ]),
                ]);
            }
        }

        return DB::transaction(function () use ($order, $lines, $tracking): Fulfillment {
            $fulfillment = Fulfillment::query()->create([
                'order_id' => $order->getKey(),
                'status' => FulfillmentShipmentStatus::Pending,
                'tracking_company' => $tracking['tracking_company'] ?? null,
                'tracking_number' => $tracking['tracking_number'] ?? null,
                'tracking_url' => $tracking['tracking_url'] ?? null,
            ]);

            foreach ($lines as $orderLineId => $quantity) {
                FulfillmentLine::query()->create([
                    'fulfillment_id' => $fulfillment->getKey(),
                    'order_line_id' => $orderLineId,
                    'quantity' => $quantity,
                ]);
            }

            $this->refreshOrderFulfillmentStatus($order);

            return $fulfillment;
        });
    }

    /**
     * Transition pending -> shipped, recording tracking data and shipped_at.
     *
     * @param  array{tracking_company?: string|null, tracking_number?: string|null, tracking_url?: string|null}|null  $tracking
     */
    public function markAsShipped(Fulfillment $fulfillment, ?array $tracking = null): void
    {
        $fulfillment->forceFill(array_filter([
            'tracking_company' => $tracking['tracking_company'] ?? null,
            'tracking_number' => $tracking['tracking_number'] ?? null,
            'tracking_url' => $tracking['tracking_url'] ?? null,
        ], fn (?string $value): bool => $value !== null))
            ->forceFill([
                'status' => FulfillmentShipmentStatus::Shipped,
                'shipped_at' => now(),
            ])
            ->save();
    }

    /**
     * Transition shipped -> delivered, recording delivered_at.
     */
    public function markAsDelivered(Fulfillment $fulfillment): void
    {
        $fulfillment->forceFill([
            'status' => FulfillmentShipmentStatus::Delivered,
            'delivered_at' => now(),
        ])->save();

        event(new FulfillmentDelivered($fulfillment));
    }

    /**
     * Auto-fulfill an order whose lines are all digital (spec 05 section
     * 11.7): one delivered fulfillment covering every line. Called after a
     * payment is captured (instant or admin-confirmed bank transfer).
     */
    public function autoFulfillDigital(Order $order): ?Fulfillment
    {
        if (! $order->isFullyDigital()) {
            return null;
        }

        return DB::transaction(function () use ($order): Fulfillment {
            $fulfillment = Fulfillment::query()->create([
                'order_id' => $order->getKey(),
                'status' => FulfillmentShipmentStatus::Delivered,
                'shipped_at' => now(),
                'delivered_at' => now(),
            ]);

            foreach ($order->lines as $orderLine) {
                FulfillmentLine::query()->create([
                    'fulfillment_id' => $fulfillment->getKey(),
                    'order_line_id' => $orderLine->getKey(),
                    'quantity' => $orderLine->quantity,
                ]);
            }

            $this->refreshOrderFulfillmentStatus($order);

            event(new FulfillmentDelivered($fulfillment));

            return $fulfillment;
        });
    }

    /**
     * Recompute the order's fulfillment status from its fulfillment lines.
     * When every line is fully fulfilled the order itself becomes fulfilled
     * and OrderFulfilled is dispatched.
     */
    protected function refreshOrderFulfillmentStatus(Order $order): void
    {
        $allFulfilled = $order->lines()
            ->get()
            ->every(fn (OrderLine $line): bool => $line->unfulfilledQuantity() === 0);

        if ($allFulfilled) {
            $order->forceFill([
                'fulfillment_status' => FulfillmentStatus::Fulfilled,
                'status' => OrderStatus::Fulfilled,
            ])->save();

            event(new OrderFulfilled($order));

            return;
        }

        $order->forceFill(['fulfillment_status' => FulfillmentStatus::Partial])->save();
    }

    /**
     * Fulfillment guard (spec 05 section 11.5): only paid or partially
     * refunded orders may be fulfilled.
     *
     * @throws FulfillmentGuardException
     */
    protected function assertFulfillmentAllowed(Order $order): void
    {
        if (! $order->financial_status->allowsFulfillment()) {
            throw FulfillmentGuardException::forFinancialStatus($order->financial_status);
        }
    }
}
