<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Events\OrderRefunded;
use App\Exceptions\RefundException;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;

class RefundService
{
    public function __construct(
        private readonly PaymentProvider $payments,
        private readonly InventoryService $inventory,
    ) {}

    public function create(Order $order, Payment $payment, int $amount, ?string $reason = null, bool $restock = false): Refund
    {
        return DB::transaction(function () use ($order, $payment, $amount, $reason, $restock): Refund {
            $order = $this->lockOrder($order);
            $payment = $this->lockPayment($order, $payment);
            $refund = $this->processLockedRefund($order, $payment, $amount, $reason);

            if ($restock) {
                $this->restockOrder($order);
            }

            return $refund;
        });
    }

    /**
     * @param  array<int, int>  $lines
     */
    public function createForLines(Order $order, Payment $payment, array $lines, ?string $reason = null, bool $restock = false, ?int $amount = null): Refund
    {
        return DB::transaction(function () use ($amount, $lines, $order, $payment, $reason, $restock): Refund {
            $order = $this->lockOrder($order);
            $payment = $this->lockPayment($order, $payment);
            $lineQuantities = $this->normalizeRefundLines($order, $lines);

            if ($lineQuantities === []) {
                throw new RefundException('invalid_refund_lines', 'Select at least one line quantity to refund.');
            }

            $refund = $this->processLockedRefund(
                $order,
                $payment,
                $amount ?? $this->calculateLineRefundAmount($order, $lineQuantities),
                $reason,
            );

            if ($restock) {
                $this->restockLines($order, $lineQuantities);
            }

            return $refund;
        });
    }

    private function lockOrder(Order $order): Order
    {
        return Order::withoutGlobalScopes()
            ->with('lines.variant.inventoryItem', 'refunds')
            ->whereKey($order->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function lockPayment(Order $order, Payment $payment): Payment
    {
        return Payment::query()
            ->where('order_id', $order->id)
            ->whereKey($payment->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function processLockedRefund(Order $order, Payment $payment, int $amount, ?string $reason): Refund
    {
        if ($payment->status !== PaymentStatus::Captured) {
            throw new RefundException('payment_not_captured', 'Only captured payments can be refunded.');
        }

        $refundable = $order->total_amount - (int) $order->refunds->where('status', RefundStatus::Processed)->sum('amount');

        if ($amount < 1 || $amount > $refundable) {
            throw new RefundException('invalid_refund_amount', 'The refund amount exceeds the remaining refundable amount.');
        }

        $result = $this->payments->refund($payment, $amount);
        $refund = $order->refunds()->create([
            'payment_id' => $payment->id,
            'amount' => $amount,
            'reason' => $reason,
            'status' => $result->status,
            'provider_refund_id' => $result->reference,
        ]);

        if (! $result->success) {
            throw new RefundException(
                $result->errorCode ?? 'refund_failed',
                $result->message ?? 'The refund could not be processed.',
            );
        }

        $totalRefunded = (int) $order->refunds()->where('status', RefundStatus::Processed)->sum('amount');
        $fullyRefunded = $totalRefunded >= $order->total_amount;

        $order->forceFill([
            'financial_status' => $fullyRefunded ? FinancialStatus::Refunded : FinancialStatus::PartiallyRefunded,
            'status' => $fullyRefunded ? OrderStatus::Refunded : $order->status,
        ])->save();

        if ($fullyRefunded) {
            $payment->forceFill(['status' => PaymentStatus::Refunded])->save();
        }

        OrderRefunded::dispatch($order, $refund);

        return $refund->refresh();
    }

    /**
     * @param  array<int, int>  $lines
     * @return array<int, int>
     */
    private function normalizeRefundLines(Order $order, array $lines): array
    {
        $normalized = [];

        foreach ($lines as $orderLineId => $quantity) {
            $quantity = (int) $quantity;

            if ($quantity < 1) {
                continue;
            }

            $line = $order->lines->firstWhere('id', (int) $orderLineId);

            if (! $line instanceof OrderLine || $quantity > $line->quantity) {
                throw new RefundException('invalid_refund_lines', 'The refund line quantity is invalid.');
            }

            $normalized[$line->id] = $quantity;
        }

        return $normalized;
    }

    /**
     * @param  array<int, int>  $lines
     */
    private function calculateLineRefundAmount(Order $order, array $lines): int
    {
        $amount = 0;

        foreach ($lines as $orderLineId => $quantity) {
            $line = $order->lines->firstWhere('id', $orderLineId);

            if (! $line instanceof OrderLine) {
                continue;
            }

            $amount += $quantity >= $line->quantity
                ? $line->total_amount
                : intdiv($line->total_amount * $quantity, $line->quantity);
        }

        if ($amount < 1) {
            throw new RefundException('invalid_refund_amount', 'The selected lines do not have a refundable amount.');
        }

        return $amount;
    }

    private function restockOrder(Order $order): void
    {
        foreach ($order->lines as $line) {
            $item = $line->variant?->inventoryItem;

            if ($item !== null) {
                $this->inventory->restock($item, $line->quantity);
            }
        }
    }

    /**
     * @param  array<int, int>  $lines
     */
    private function restockLines(Order $order, array $lines): void
    {
        foreach ($lines as $orderLineId => $quantity) {
            $line = $order->lines->firstWhere('id', $orderLineId);
            $item = $line?->variant?->inventoryItem;

            if ($item !== null) {
                $this->inventory->restock($item, $quantity);
            }
        }
    }
}
