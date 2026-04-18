<?php

namespace App\Services;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Events\OrderFulfilled;
use App\Exceptions\FulfillmentGuardException;
use App\Models\Fulfillment;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FulfillmentService
{
    /**
     * @param  array<int, array{order_line_id: int, quantity: int}>  $lines
     * @param  array{tracking_company?: ?string, tracking_number?: ?string, tracking_url?: ?string}  $tracking
     */
    public function create(Order $order, array $lines, array $tracking = []): Fulfillment
    {
        $this->guardPaid($order);

        if (empty($lines)) {
            throw ValidationException::withMessages(['lines' => 'At least one line must be specified.']);
        }

        return DB::transaction(function () use ($order, $lines, $tracking): Fulfillment {
            $order->loadMissing('lines.fulfillmentLines');

            foreach ($lines as $entry) {
                $orderLine = $order->lines->firstWhere('id', $entry['order_line_id'] ?? null);
                $qty = (int) ($entry['quantity'] ?? 0);

                if (! $orderLine) {
                    throw ValidationException::withMessages(['lines' => 'Unknown order line.']);
                }

                $already = $orderLine->fulfilledQuantity();
                $remaining = $orderLine->quantity - $already;

                if ($qty <= 0 || $qty > $remaining) {
                    throw ValidationException::withMessages(['lines' => "Quantity exceeds unfulfilled amount ({$remaining})."]);
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

            foreach ($lines as $entry) {
                $fulfillment->lines()->create([
                    'order_line_id' => (int) $entry['order_line_id'],
                    'quantity' => (int) $entry['quantity'],
                ]);
            }

            $this->recomputeOrderFulfillment($order->fresh(['lines.fulfillmentLines']));

            return $fulfillment;
        });
    }

    /**
     * @param  array<string, ?string>  $tracking
     */
    public function markAsShipped(Fulfillment $fulfillment, array $tracking = []): Fulfillment
    {
        if ($fulfillment->status === FulfillmentShipmentStatus::Delivered) {
            return $fulfillment;
        }

        $fulfillment->status = FulfillmentShipmentStatus::Shipped;
        $fulfillment->shipped_at = now();
        foreach (['tracking_company', 'tracking_number', 'tracking_url'] as $field) {
            if (array_key_exists($field, $tracking)) {
                $fulfillment->{$field} = $tracking[$field];
            }
        }
        $fulfillment->save();

        return $fulfillment;
    }

    public function markAsDelivered(Fulfillment $fulfillment): Fulfillment
    {
        $fulfillment->status = FulfillmentShipmentStatus::Delivered;
        $fulfillment->delivered_at = now();
        $fulfillment->save();

        return $fulfillment;
    }

    protected function guardPaid(Order $order): void
    {
        if (! in_array($order->financial_status, [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded], true)) {
            throw new FulfillmentGuardException(
                'Fulfillment cannot be created until payment is confirmed.'
            );
        }
    }

    protected function recomputeOrderFulfillment(Order $order): void
    {
        $allFulfilled = true;
        $anyFulfilled = false;

        foreach ($order->lines as $line) {
            $filled = $line->fulfilledQuantity();
            if ($filled > 0) {
                $anyFulfilled = true;
            }
            if ($filled < $line->quantity) {
                $allFulfilled = false;
            }
        }

        if ($allFulfilled) {
            $order->fulfillment_status = FulfillmentStatus::Fulfilled;
            $order->status = OrderStatus::Fulfilled;
            $order->save();
            OrderFulfilled::dispatch($order);
        } elseif ($anyFulfilled) {
            $order->fulfillment_status = FulfillmentStatus::Partial;
            $order->save();
        }
    }
}
