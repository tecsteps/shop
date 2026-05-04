<?php

namespace App\Services;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Events\FulfillmentCreated;
use App\Events\FulfillmentDelivered;
use App\Events\FulfillmentShipped;
use App\Exceptions\InvalidFulfillmentOperationException;
use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use App\Models\Order;
use App\Models\OrderLine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FulfillmentService
{
    /**
     * @param  array<int|string, int>  $lines
     * @param  array<string, mixed>  $trackingData
     */
    public function create(Order $order, array $lines, array $trackingData = []): Fulfillment
    {
        return DB::transaction(function () use ($order, $lines, $trackingData): Fulfillment {
            $order = $this->freshOrder($order);
            $this->assertCanFulfill($order);

            $requestedLines = $this->normalizeLines($lines);

            if ($requestedLines->isEmpty()) {
                throw InvalidFulfillmentOperationException::because('At least one fulfillment line is required.');
            }

            $requestedLines->each(function (int $quantity, int $lineId) use ($order): void {
                $line = $order->lines->firstWhere('id', $lineId);

                if (! $line instanceof OrderLine) {
                    throw InvalidFulfillmentOperationException::because('Fulfillment line does not belong to this order.');
                }

                $remaining = $line->quantity - $this->fulfilledQuantity($line);

                if ($quantity > $remaining) {
                    throw InvalidFulfillmentOperationException::because('Fulfillment quantity exceeds the unfulfilled quantity.');
                }
            });

            $fulfillment = Fulfillment::query()->create([
                'order_id' => $order->getKey(),
                'status' => FulfillmentShipmentStatus::Pending,
                'tracking_company' => data_get($trackingData, 'tracking_company'),
                'tracking_number' => data_get($trackingData, 'tracking_number'),
                'tracking_url' => data_get($trackingData, 'tracking_url'),
            ]);

            $requestedLines->each(function (int $quantity, int $lineId) use ($fulfillment): void {
                $fulfillment->lines()->create([
                    'order_line_id' => $lineId,
                    'quantity' => $quantity,
                ]);
            });

            $this->updateOrderFulfillmentStatus($order);

            $fulfillment = $fulfillment->refresh()->load('lines.orderLine');

            event(new FulfillmentCreated($fulfillment));

            return $fulfillment;
        });
    }

    public function markShipped(Fulfillment $fulfillment): Fulfillment
    {
        return DB::transaction(function () use ($fulfillment): Fulfillment {
            $fulfillment = $this->freshFulfillment($fulfillment);

            if ($fulfillment->status !== FulfillmentShipmentStatus::Pending) {
                throw InvalidFulfillmentOperationException::because('Only pending fulfillments can be marked as shipped.');
            }

            $fulfillment->forceFill([
                'status' => FulfillmentShipmentStatus::Shipped,
                'shipped_at' => now(),
            ])->save();

            $fulfillment = $fulfillment->refresh()->load('lines.orderLine');

            event(new FulfillmentShipped($fulfillment));

            return $fulfillment;
        });
    }

    public function markDelivered(Fulfillment $fulfillment): Fulfillment
    {
        return DB::transaction(function () use ($fulfillment): Fulfillment {
            $fulfillment = $this->freshFulfillment($fulfillment);

            if ($fulfillment->status !== FulfillmentShipmentStatus::Shipped) {
                throw InvalidFulfillmentOperationException::because('Only shipped fulfillments can be marked as delivered.');
            }

            $fulfillment->forceFill([
                'status' => FulfillmentShipmentStatus::Delivered,
                'delivered_at' => now(),
            ])->save();

            $fulfillment = $fulfillment->refresh()->load('lines.orderLine');

            event(new FulfillmentDelivered($fulfillment));

            return $fulfillment;
        });
    }

    public function autoFulfillDigital(Order $order): ?Fulfillment
    {
        return DB::transaction(function () use ($order): ?Fulfillment {
            $order = $this->freshOrder($order);

            if ($order->fulfillments()->exists() || $order->lines->isEmpty()) {
                return null;
            }

            $allDigital = $order->lines->every(fn (OrderLine $line): bool => $line->variant?->requires_shipping === false);

            if (! $allDigital) {
                return null;
            }

            $fulfillment = Fulfillment::query()->create([
                'order_id' => $order->getKey(),
                'status' => FulfillmentShipmentStatus::Delivered,
                'shipped_at' => now(),
                'delivered_at' => now(),
            ]);

            $order->lines->each(function (OrderLine $line) use ($fulfillment): void {
                $fulfillment->lines()->create([
                    'order_line_id' => $line->getKey(),
                    'quantity' => $line->quantity,
                ]);
            });

            $order->forceFill([
                'status' => OrderStatus::Fulfilled,
                'fulfillment_status' => FulfillmentStatus::Fulfilled,
            ])->save();

            $fulfillment = $fulfillment->refresh()->load('lines.orderLine');

            event(new FulfillmentCreated($fulfillment));
            event(new FulfillmentDelivered($fulfillment));

            return $fulfillment;
        });
    }

    private function freshOrder(Order $order): Order
    {
        return Order::withoutGlobalScopes()
            ->with([
                'lines.variant' => fn ($query) => $query->withoutGlobalScopes(),
                'fulfillments.lines',
            ])
            ->whereKey($order->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function freshFulfillment(Fulfillment $fulfillment): Fulfillment
    {
        return Fulfillment::query()
            ->with('lines.orderLine')
            ->whereKey($fulfillment->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function assertCanFulfill(Order $order): void
    {
        if (! in_array($order->financial_status, [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded], true)) {
            throw InvalidFulfillmentOperationException::because('Fulfillment cannot be created until payment is confirmed.');
        }
    }

    /**
     * @param  array<int|string, int>  $lines
     * @return Collection<int, int>
     */
    private function normalizeLines(array $lines): Collection
    {
        return collect($lines)
            ->mapWithKeys(fn (mixed $quantity, int|string $lineId): array => [(int) $lineId => (int) $quantity])
            ->filter(fn (int $quantity): bool => $quantity > 0);
    }

    private function fulfilledQuantity(OrderLine $line): int
    {
        return (int) FulfillmentLine::query()
            ->where('order_line_id', $line->getKey())
            ->sum('quantity');
    }

    private function updateOrderFulfillmentStatus(Order $order): void
    {
        $allFulfilled = $order->lines->every(function (OrderLine $line): bool {
            return $this->fulfilledQuantity($line) >= $line->quantity;
        });

        $order->forceFill([
            'status' => $allFulfilled ? OrderStatus::Fulfilled : $order->status,
            'fulfillment_status' => $allFulfilled ? FulfillmentStatus::Fulfilled : FulfillmentStatus::Partial,
        ])->save();
    }
}
