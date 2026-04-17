<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
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
        private PaymentProvider $paymentProvider,
        private InventoryService $inventoryService,
    ) {}

    public function create(
        Order $order,
        Payment $payment,
        int $amount,
        ?string $reason = null,
        bool $restock = false,
    ): Refund {
        // Validate amount does not exceed remaining refundable amount
        $totalRefunded = $order->refunds()
            ->where('status', '!=', RefundStatus::Failed->value)
            ->sum('amount');

        $refundable = $payment->amount - $totalRefunded;

        if ($amount > $refundable) {
            throw new RuntimeException(
                "Refund amount ({$amount}) exceeds remaining refundable amount ({$refundable})."
            );
        }

        if ($amount <= 0) {
            throw new RuntimeException('Refund amount must be greater than zero.');
        }

        return DB::transaction(function () use ($order, $payment, $amount, $reason, $restock) {
            // Call provider to process refund
            $result = $this->paymentProvider->refund($payment, $amount);

            $refund = Refund::create([
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'amount' => $amount,
                'reason' => $reason,
                'status' => $result->success ? RefundStatus::Processed : RefundStatus::Failed,
                'provider_refund_id' => $result->providerRefundId,
                'created_at' => now()->toIso8601String(),
            ]);

            if (! $result->success) {
                return $refund;
            }

            // Update financial status
            $totalRefunded = $order->refunds()
                ->where('status', RefundStatus::Processed->value)
                ->sum('amount');

            if ($totalRefunded >= $order->total_amount) {
                $order->update([
                    'financial_status' => FinancialStatus::Refunded,
                    'status' => OrderStatus::Refunded,
                ]);
            } else {
                $order->update([
                    'financial_status' => FinancialStatus::PartiallyRefunded,
                ]);
            }

            // Restock inventory if requested
            if ($restock) {
                $order->load('lines.variant.inventoryItem');
                foreach ($order->lines as $line) {
                    if ($line->variant?->inventoryItem) {
                        $this->inventoryService->restock($line->variant->inventoryItem, $line->quantity);
                    }
                }
            }

            OrderRefunded::dispatch($order, $refund);

            return $refund;
        });
    }
}
