<?php

namespace App\Services;

use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\RefundStatus;
use App\Events\OrderRefunded;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RefundService
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly InventoryService $inventory,
    ) {}

    /**
     * @param  array<int, array{order_line_id: int, quantity: int}>  $restockLines
     */
    public function create(
        Order $order,
        Payment $payment,
        int $amount,
        ?string $reason = null,
        bool $restock = false,
        array $restockLines = [],
    ): Refund {
        if ($amount <= 0) {
            throw new RuntimeException('Refund amount must be positive.');
        }

        $remaining = $order->remainingRefundable();

        if ($amount > $remaining) {
            throw new RuntimeException("Refund amount {$amount} exceeds refundable {$remaining}.");
        }

        return DB::transaction(function () use ($order, $payment, $amount, $reason, $restock, $restockLines): Refund {
            $this->payments->refund($payment, $amount);

            $refund = Refund::query()->create([
                'order_id' => $order->getKey(),
                'payment_id' => $payment->getKey(),
                'amount' => $amount,
                'reason' => $reason,
                'status' => RefundStatus::Processed->value,
                'provider_refund_id' => 'mock_refund_'.uniqid(),
                'created_at' => now(),
            ]);

            if ($restock) {
                $this->restockLines($order, $restockLines);
            }

            $totalRefunded = $order->totalRefunded() + 0;

            if ($totalRefunded >= $order->total_amount) {
                $order->financial_status = FinancialStatus::Refunded;
                $order->status = OrderStatus::Refunded;
            } else {
                $order->financial_status = FinancialStatus::PartiallyRefunded;
            }

            $order->save();

            OrderRefunded::dispatch($order->refresh(), $refund);

            return $refund;
        });
    }

    /**
     * @param  array<int, array{order_line_id: int, quantity: int}>  $restockLines
     */
    protected function restockLines(Order $order, array $restockLines): void
    {
        if (empty($restockLines)) {
            foreach ($order->lines()->with('variant')->get() as $line) {
                if ($line->variant !== null) {
                    $this->inventory->restock($line->variant, (int) $line->quantity);
                }
            }

            return;
        }

        foreach ($restockLines as $entry) {
            $line = $order->lines()->find($entry['order_line_id']);

            if ($line === null || $line->variant === null) {
                continue;
            }

            $this->inventory->restock($line->variant, (int) $entry['quantity']);
        }
    }
}
