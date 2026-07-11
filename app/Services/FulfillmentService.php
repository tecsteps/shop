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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FulfillmentService
{
    /** @param array<int, int> $lines
     * @param  array<string, string|null>  $tracking
     */
    public function create(Order $order, array $lines, ?array $tracking = null): Fulfillment
    {
        if (! in_array($order->financial_status, [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded], true)) {
            throw new FulfillmentGuardException;
        }

        return DB::transaction(function () use ($order, $lines, $tracking): Fulfillment {
            $fulfillment = $order->fulfillments()->create([
                'tracking_company' => $tracking['tracking_company'] ?? null,
                'tracking_number' => $tracking['tracking_number'] ?? null,
                'tracking_url' => $tracking['tracking_url'] ?? null,
            ]);

            foreach ($lines as $orderLineId => $quantity) {
                $orderLine = $order->lines()->findOrFail($orderLineId);
                $fulfilledQuantity = $orderLine->fulfillmentLines()->sum('quantity');

                if ($quantity < 1 || $quantity > $orderLine->quantity - $fulfilledQuantity) {
                    throw ValidationException::withMessages(['lines' => 'A fulfillment quantity exceeds the unfulfilled quantity.']);
                }

                $fulfillment->lines()->create(['order_line_id' => $orderLine->id, 'quantity' => $quantity]);
            }

            $order->load('lines.fulfillmentLines');
            $allFulfilled = $order->lines->every(fn ($line): bool => $line->fulfillmentLines->sum('quantity') >= $line->quantity);
            $order->update([
                'fulfillment_status' => $allFulfilled ? FulfillmentStatus::Fulfilled : FulfillmentStatus::Partial,
                'status' => $allFulfilled ? OrderStatus::Fulfilled : $order->status,
            ]);

            FulfillmentCreated::dispatch($fulfillment);

            if ($allFulfilled) {
                OrderFulfilled::dispatch($order);
            }

            return $fulfillment->refresh()->load('lines');
        });
    }

    /** @param array<string, string|null> $tracking */
    public function markAsShipped(Fulfillment $fulfillment, ?array $tracking = null): void
    {
        if ($fulfillment->status !== FulfillmentShipmentStatus::Pending) {
            throw ValidationException::withMessages(['fulfillment' => 'Only pending fulfillments can be shipped.']);
        }

        $fulfillment->update([
            'status' => FulfillmentShipmentStatus::Shipped,
            'tracking_company' => $tracking['tracking_company'] ?? $fulfillment->tracking_company,
            'tracking_number' => $tracking['tracking_number'] ?? $fulfillment->tracking_number,
            'tracking_url' => $tracking['tracking_url'] ?? $fulfillment->tracking_url,
            'shipped_at' => now(),
        ]);
        FulfillmentShipped::dispatch($fulfillment);
    }

    public function markAsDelivered(Fulfillment $fulfillment): void
    {
        if ($fulfillment->status !== FulfillmentShipmentStatus::Shipped) {
            throw ValidationException::withMessages(['fulfillment' => 'Only shipped fulfillments can be delivered.']);
        }

        $fulfillment->update(['status' => FulfillmentShipmentStatus::Delivered, 'delivered_at' => now()]);
        FulfillmentDelivered::dispatch($fulfillment);
    }
}
