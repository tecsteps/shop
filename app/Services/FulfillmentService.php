<?php

namespace App\Services;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentOrderStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\OrderStatus;
use App\Events\OrderFulfilled;
use App\Exceptions\FulfillmentGuardException;
use App\Models\Fulfillment;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FulfillmentService
{
    /** @param array<int, int> $lines
     * @param  array{tracking_company?: string|null, tracking_number?: string|null, tracking_url?: string|null}  $trackingData
     */
    public function create(Order $order, array $lines, array $trackingData = []): Fulfillment
    {
        if (! in_array($order->financial_status, [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded], true)) {
            throw new FulfillmentGuardException('Fulfillment cannot be created until payment is confirmed.');
        }

        if ($lines === []) {
            throw ValidationException::withMessages(['lines' => 'At least one fulfillment line is required.']);
        }

        return DB::transaction(function () use ($order, $lines, $trackingData): Fulfillment {
            $order->loadMissing('lines.fulfillmentLines');

            foreach ($lines as $lineId => $quantity) {
                $line = $order->lines->find($lineId);
                $fulfilled = $line?->fulfillmentLines->sum('quantity') ?? 0;

                if (! $line || $quantity <= 0 || $quantity > ($line->quantity - $fulfilled)) {
                    throw ValidationException::withMessages(['lines' => 'Fulfillment quantity exceeds the remaining quantity.']);
                }
            }

            $fulfillment = $order->fulfillments()->create([
                'status' => FulfillmentShipmentStatus::Pending,
                ...$trackingData,
            ]);

            foreach ($lines as $lineId => $quantity) {
                $fulfillment->lines()->create(['order_line_id' => $lineId, 'quantity' => $quantity]);
            }

            $this->updateOrderStatus($order);

            return $fulfillment->load('lines');
        });
    }

    public function markShipped(Fulfillment $fulfillment): void
    {
        if ($fulfillment->status !== FulfillmentShipmentStatus::Pending) {
            throw ValidationException::withMessages(['status' => 'Only pending fulfillments can be shipped.']);
        }

        $fulfillment->update(['status' => FulfillmentShipmentStatus::Shipped, 'shipped_at' => now()]);
    }

    public function markDelivered(Fulfillment $fulfillment): void
    {
        if ($fulfillment->status !== FulfillmentShipmentStatus::Shipped) {
            throw ValidationException::withMessages(['status' => 'Only shipped fulfillments can be delivered.']);
        }

        $fulfillment->update(['status' => FulfillmentShipmentStatus::Delivered]);
    }

    public function autoFulfillDigitalOrder(Order $order): ?Fulfillment
    {
        $order->loadMissing('lines.variant');

        if ($order->lines->isEmpty() || $order->lines->contains(fn ($line): bool => $line->variant?->requires_shipping ?? true)) {
            return null;
        }

        return DB::transaction(function () use ($order): Fulfillment {
            $fulfillment = $order->fulfillments()->create([
                'status' => FulfillmentShipmentStatus::Delivered,
                'shipped_at' => now(),
            ]);

            foreach ($order->lines as $line) {
                $fulfillment->lines()->create(['order_line_id' => $line->id, 'quantity' => $line->quantity]);
            }

            $order->update([
                'fulfillment_status' => FulfillmentOrderStatus::Fulfilled,
                'status' => OrderStatus::Fulfilled,
            ]);
            OrderFulfilled::dispatch($order);

            return $fulfillment;
        });
    }

    private function updateOrderStatus(Order $order): void
    {
        $order->load('lines.fulfillmentLines');
        $allFulfilled = $order->lines->every(
            fn ($line): bool => $line->fulfillmentLines->sum('quantity') >= $line->quantity
        );

        $order->update([
            'fulfillment_status' => $allFulfilled ? FulfillmentOrderStatus::Fulfilled : FulfillmentOrderStatus::Partial,
            'status' => $allFulfilled ? OrderStatus::Fulfilled : $order->status,
        ]);

        if ($allFulfilled) {
            OrderFulfilled::dispatch($order);
        }
    }
}
