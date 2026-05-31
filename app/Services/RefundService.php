<?php

namespace App\Services;

use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Events\OrderRefunded;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Processes refunds against a captured payment and optionally restocks
 * inventory.
 *
 * A refund may not exceed the remaining refundable amount on the order. A full
 * refund moves the order to `refunded`; a partial refund to
 * `partially_refunded`. When the restock flag is set, refunded line quantities
 * are returned to stock.
 */
class RefundService
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly InventoryService $inventory,
    ) {}

    /**
     * Create a refund against an order's payment.
     *
     * `$lines` maps order_line_id => quantity for line-level restock. When
     * `$amount` is null it is derived from the lines (or defaults to the full
     * remaining refundable amount).
     *
     * @param  array<int, int>  $lines  order_line_id => quantity
     */
    public function create(
        Order $order,
        Payment $payment,
        ?int $amount = null,
        ?string $reason = null,
        bool $restock = false,
        array $lines = [],
    ): Refund {
        return DB::transaction(function () use ($order, $payment, $amount, $reason, $restock, $lines): Refund {
            $refundable = $payment->amount - $payment->refunds()->sum('amount');

            $amount = $this->resolveAmount($order, $amount, $lines, $refundable);

            if ($amount <= 0) {
                throw new InvalidArgumentException('Refund amount must be greater than zero.');
            }

            if ($amount > $refundable) {
                throw new InvalidArgumentException(
                    "Refund amount {$amount} exceeds the refundable amount {$refundable}.",
                );
            }

            $result = $this->payments->refund($payment, $amount);

            $refund = $order->refunds()->create([
                'payment_id' => $payment->id,
                'amount' => $amount,
                'reason' => $reason,
                'status' => ($result->success ? RefundStatus::Processed : RefundStatus::Failed)->value,
                'provider_refund_id' => $result->providerRefundId,
            ]);

            $this->updateOrderFinancialStatus($order, $payment);

            if ($restock) {
                $this->restockLines($order, $lines);
            }

            OrderRefunded::dispatch($order->refresh());

            return $refund;
        });
    }

    /**
     * @param  array<int, int>  $lines
     */
    private function resolveAmount(Order $order, ?int $amount, array $lines, int $refundable): int
    {
        if ($amount !== null) {
            return $amount;
        }

        if ($lines !== []) {
            return $this->amountFromLines($order, $lines);
        }

        return $refundable;
    }

    /**
     * @param  array<int, int>  $lines
     */
    private function amountFromLines(Order $order, array $lines): int
    {
        $total = 0;

        foreach ($lines as $orderLineId => $quantity) {
            $line = $order->lines->firstWhere('id', $orderLineId);

            if ($line !== null) {
                $total += $line->unit_price_amount * $quantity;
            }
        }

        return $total;
    }

    private function updateOrderFinancialStatus(Order $order, Payment $payment): void
    {
        $totalRefunded = $order->refunds()->sum('amount');

        if ($totalRefunded >= $order->total_amount) {
            $order->update([
                'financial_status' => FinancialStatus::Refunded->value,
                'status' => OrderStatus::Refunded->value,
            ]);
            $payment->update(['status' => PaymentStatus::Refunded->value]);
        } else {
            $order->update(['financial_status' => FinancialStatus::PartiallyRefunded->value]);
        }
    }

    /**
     * @param  array<int, int>  $lines
     */
    private function restockLines(Order $order, array $lines): void
    {
        foreach ($lines as $orderLineId => $quantity) {
            $line = $order->lines->firstWhere('id', $orderLineId);
            $item = $line?->variant_id === null
                ? null
                : ProductVariant::query()->find($line->variant_id)?->inventoryItem;

            if ($item !== null && $quantity > 0) {
                $this->inventory->restock($item, $quantity);
            }
        }
    }
}
