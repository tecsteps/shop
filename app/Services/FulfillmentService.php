<?php

namespace App\Services;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Events\FulfillmentCreated;
use App\Events\FulfillmentDelivered;
use App\Events\FulfillmentShipped;
use App\Exceptions\FulfillmentGuardException;
use App\Exceptions\InvalidFulfillmentTransitionException;
use App\Models\Fulfillment;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Fulfillment creation and status management per spec 05 section 11.5.
 */
class FulfillmentService
{
    /**
     * @param  array<int, int>  $lines  Map of order_line_id to quantity.
     * @param  array<string, mixed>|null  $tracking
     */
    public function create(Order $order, array $lines, ?array $tracking = null): Fulfillment
    {
        return DB::transaction(function () use ($order, $lines, $tracking): Fulfillment {
            $fresh = $order->refresh();

            if (! in_array($fresh->financial_status, [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded], true)) {
                throw new FulfillmentGuardException('Fulfillment cannot be created until payment is confirmed.');
            }

            if ($lines === []) {
                throw ValidationException::withMessages([
                    'line_items' => ['At least one line item is required.'],
                ]);
            }

            $orderLines = $fresh->orderLines()->with('fulfillmentLines')->get()->keyBy('id');

            foreach ($lines as $orderLineId => $quantity) {
                $orderLine = $orderLines->get((int) $orderLineId);

                if ($orderLine === null) {
                    throw ValidationException::withMessages([
                        'line_items' => ['One or more line items do not belong to this order.'],
                    ]);
                }

                if ($quantity < 1) {
                    throw ValidationException::withMessages([
                        'line_items' => ['The quantity must be at least 1.'],
                    ]);
                }

                $fulfilledSoFar = $orderLine->fulfillmentLines->sum('quantity');
                $unfulfilled = $orderLine->quantity - $fulfilledSoFar;

                if ($quantity > $unfulfilled) {
                    throw ValidationException::withMessages([
                        'line_items' => ['The quantity exceeds the unfulfilled quantity for this line.'],
                    ]);
                }
            }

            $fulfillment = $fresh->fulfillments()->create([
                'status' => FulfillmentShipmentStatus::Pending,
                'tracking_company' => $tracking['tracking_company'] ?? null,
                'tracking_number' => $tracking['tracking_number'] ?? null,
                'tracking_url' => $tracking['tracking_url'] ?? null,
                'shipped_at' => null,
            ]);

            foreach ($lines as $orderLineId => $quantity) {
                $fulfillment->fulfillmentLines()->create([
                    'order_line_id' => (int) $orderLineId,
                    'quantity' => $quantity,
                ]);
            }

            $this->refreshOrderStatus($fresh->refresh());

            event(new FulfillmentCreated($fulfillment->refresh()));

            return $fulfillment->refresh();
        });
    }

    /**
     * @param  array<string, mixed>|null  $tracking
     */
    public function markAsShipped(Fulfillment $fulfillment, ?array $tracking = null): Fulfillment
    {
        return DB::transaction(function () use ($fulfillment, $tracking): Fulfillment {
            $fresh = $fulfillment->refresh();

            if ($fresh->status !== FulfillmentShipmentStatus::Pending) {
                throw new InvalidFulfillmentTransitionException('Only pending fulfillments can be marked as shipped.');
            }

            if ($tracking !== null) {
                foreach (['tracking_company', 'tracking_number', 'tracking_url'] as $field) {
                    if (array_key_exists($field, $tracking)) {
                        $fresh->{$field} = $tracking[$field];
                    }
                }
            }

            $fresh->status = FulfillmentShipmentStatus::Shipped;
            $fresh->shipped_at = now();
            $fresh->save();

            event(new FulfillmentShipped($fresh->refresh()));

            return $fresh->refresh();
        });
    }

    public function markAsDelivered(Fulfillment $fulfillment): Fulfillment
    {
        return DB::transaction(function () use ($fulfillment): Fulfillment {
            $fresh = $fulfillment->refresh();

            if ($fresh->status !== FulfillmentShipmentStatus::Shipped) {
                throw new InvalidFulfillmentTransitionException('Only shipped fulfillments can be marked as delivered.');
            }

            $fresh->status = FulfillmentShipmentStatus::Delivered;
            $fresh->save();

            event(new FulfillmentDelivered($fresh->refresh()));

            return $fresh->refresh();
        });
    }

    public function cancel(Fulfillment $fulfillment): void
    {
        DB::transaction(function () use ($fulfillment): void {
            $fresh = $fulfillment->refresh();

            if ($fresh->status !== FulfillmentShipmentStatus::Pending) {
                throw new InvalidFulfillmentTransitionException('Only pending fulfillments can be cancelled.');
            }

            $order = $fresh->order;
            $fresh->fulfillmentLines()->delete();
            $fresh->delete();

            $this->refreshOrderStatus($order->refresh());
        });
    }

    public function autoFulfillDigital(Order $order): ?Fulfillment
    {
        return DB::transaction(function () use ($order): ?Fulfillment {
            $fresh = $order->refresh();

            if (! in_array($fresh->financial_status, [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded], true)) {
                return null;
            }

            $lines = $fresh->orderLines()->with('variant')->get();

            if ($lines->isEmpty()) {
                return null;
            }

            foreach ($lines as $line) {
                if ($line->variant === null || $line->variant->requires_shipping) {
                    return null;
                }
            }

            $fulfillment = $fresh->fulfillments()->create([
                'status' => FulfillmentShipmentStatus::Delivered,
                'tracking_company' => null,
                'tracking_number' => null,
                'tracking_url' => null,
                'shipped_at' => now(),
            ]);

            foreach ($lines as $line) {
                $fulfillment->fulfillmentLines()->create([
                    'order_line_id' => $line->getKey(),
                    'quantity' => $line->quantity,
                ]);
            }

            $fresh->fulfillment_status = FulfillmentStatus::Fulfilled;
            $fresh->status = OrderStatus::Fulfilled;
            $fresh->save();

            event(new FulfillmentCreated($fulfillment->refresh()));

            return $fulfillment->refresh();
        });
    }

    protected function refreshOrderStatus(Order $order): void
    {
        $allFulfilled = true;

        foreach ($order->orderLines()->with('fulfillmentLines')->get() as $line) {
            if ($line->fulfillmentLines->sum('quantity') < $line->quantity) {
                $allFulfilled = false;

                break;
            }
        }

        if ($allFulfilled) {
            $order->fulfillment_status = FulfillmentStatus::Fulfilled;
            $order->status = OrderStatus::Fulfilled;
        } else {
            $order->fulfillment_status = FulfillmentStatus::Partial;
        }

        $order->save();
    }
}
