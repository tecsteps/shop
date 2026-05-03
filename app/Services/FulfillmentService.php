<?php

namespace App\Services;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Events\FulfillmentDelivered;
use App\Events\FulfillmentShipped;
use App\Events\OrderFulfilled;
use App\Exceptions\FulfillmentGuardException;
use App\Exceptions\FulfillmentQuantityException;
use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class FulfillmentService
{
    /**
     * @param  array<int, int>  $lines
     * @param  array<string, string|null>|null  $tracking
     */
    public function create(Order $order, array $lines, ?array $tracking = null): Fulfillment
    {
        return DB::transaction(function () use ($order, $lines, $tracking): Fulfillment {
            $order = $this->lockOrder($order);
            $this->guardCanFulfill($order);

            if ($lines === []) {
                throw new FulfillmentQuantityException('A fulfillment must contain at least one line.');
            }

            foreach ($lines as $orderLineId => $quantity) {
                $this->validateFulfillmentQuantity($order, (int) $orderLineId, (int) $quantity);
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

            $this->updateOrderFulfillmentStatus($order);

            return $fulfillment->refresh()->load('lines.orderLine');
        });
    }

    /**
     * @param  array<string, string|null>|null  $tracking
     */
    public function markAsShipped(Fulfillment $fulfillment, ?array $tracking = null): void
    {
        DB::transaction(function () use ($fulfillment, $tracking): void {
            $fulfillment = Fulfillment::query()
                ->whereKey($fulfillment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($fulfillment->status !== FulfillmentShipmentStatus::Pending) {
                throw new FulfillmentGuardException('Only pending fulfillments may be marked as shipped.');
            }

            $fulfillment->forceFill([
                'status' => FulfillmentShipmentStatus::Shipped,
                'tracking_company' => $tracking['tracking_company'] ?? $fulfillment->tracking_company,
                'tracking_number' => $tracking['tracking_number'] ?? $fulfillment->tracking_number,
                'tracking_url' => $tracking['tracking_url'] ?? $fulfillment->tracking_url,
                'shipped_at' => now(),
            ])->save();

            FulfillmentShipped::dispatch($fulfillment->refresh());
        });
    }

    public function markAsDelivered(Fulfillment $fulfillment): void
    {
        DB::transaction(function () use ($fulfillment): void {
            $fulfillment = Fulfillment::query()
                ->whereKey($fulfillment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($fulfillment->status !== FulfillmentShipmentStatus::Shipped) {
                throw new FulfillmentGuardException('Only shipped fulfillments may be marked as delivered.');
            }

            $fulfillment->forceFill([
                'status' => FulfillmentShipmentStatus::Delivered,
                'delivered_at' => now(),
            ])->save();

            FulfillmentDelivered::dispatch($fulfillment->refresh());
        });
    }

    private function guardCanFulfill(Order $order): void
    {
        if (! in_array($order->financial_status, [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded], true)) {
            throw new FulfillmentGuardException('Fulfillment cannot be created until payment is confirmed.');
        }
    }

    private function validateFulfillmentQuantity(Order $order, int $orderLineId, int $quantity): void
    {
        $line = $order->lines->firstWhere('id', $orderLineId);

        if ($line === null || $quantity < 1) {
            throw new FulfillmentQuantityException('The fulfillment line quantity is invalid.');
        }

        $fulfilled = (int) FulfillmentLine::query()
            ->where('order_line_id', $orderLineId)
            ->sum('quantity');

        if ($quantity > ($line->quantity - $fulfilled)) {
            throw new FulfillmentQuantityException('The fulfillment quantity exceeds the unfulfilled quantity.');
        }
    }

    private function updateOrderFulfillmentStatus(Order $order): void
    {
        $order->load('lines');
        $fulfilledLineIds = 0;

        foreach ($order->lines as $line) {
            $fulfilled = (int) FulfillmentLine::query()
                ->where('order_line_id', $line->id)
                ->sum('quantity');

            if ($fulfilled >= $line->quantity) {
                $fulfilledLineIds++;
            }
        }

        if ($fulfilledLineIds === $order->lines->count()) {
            $order->forceFill([
                'status' => OrderStatus::Fulfilled,
                'fulfillment_status' => FulfillmentStatus::Fulfilled,
            ])->save();

            OrderFulfilled::dispatch($order);

            return;
        }

        $order->forceFill([
            'fulfillment_status' => FulfillmentStatus::Partial,
        ])->save();
    }

    private function lockOrder(Order $order): Order
    {
        return Order::withoutGlobalScopes()
            ->with('lines', 'fulfillments.lines')
            ->whereKey($order->id)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
