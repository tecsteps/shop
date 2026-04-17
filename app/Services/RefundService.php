<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\RefundStatus;
use App\Events\OrderRefunded;
use App\Exceptions\InvalidRefundException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;

class RefundService
{
    public function __construct(
        private PaymentProvider $paymentProvider,
        private InventoryService $inventoryService,
    ) {}

    /**
     * Process a refund for an order.
     *
     * @param  array<int, int>|null  $lines  Map of order_line_id => quantity to refund
     */
    public function create(
        Order $order,
        Payment $payment,
        ?int $amount = null,
        ?string $reason = null,
        bool $restock = false,
        ?array $lines = null,
    ): Refund {
        return DB::transaction(function () use ($order, $payment, $amount, $reason, $restock, $lines) {
            // Calculate refundable amount
            $totalRefunded = $order->refunds()
                ->where('status', RefundStatus::Processed)
                ->sum('amount');
            $refundable = $order->total_amount - $totalRefunded;

            // Determine refund amount
            if ($amount !== null) {
                if ($amount <= 0) {
                    throw new InvalidRefundException('Refund amount must be positive.');
                }
                if ($amount > $refundable) {
                    throw new InvalidRefundException(
                        "Refund amount ({$amount}) exceeds refundable amount ({$refundable})."
                    );
                }
            } elseif ($lines !== null) {
                // Calculate from lines
                $amount = 0;
                foreach ($lines as $lineId => $qty) {
                    $orderLine = $order->lines()->find($lineId);
                    if ($orderLine) {
                        $lineUnitPrice = $orderLine->total_amount / $orderLine->quantity;
                        $amount += (int) round($lineUnitPrice * $qty);
                    }
                }
                if ($amount > $refundable) {
                    throw new InvalidRefundException(
                        "Calculated refund amount ({$amount}) exceeds refundable amount ({$refundable})."
                    );
                }
            } else {
                // Full refund
                $amount = $refundable;
            }

            if ($amount <= 0) {
                throw new InvalidRefundException('Refund amount must be positive.');
            }

            // Call payment provider for refund
            $refundResult = $this->paymentProvider->refund($payment, $amount);

            // Create refund record
            $refund = Refund::create([
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'amount' => $amount,
                'reason' => $reason,
                'status' => $refundResult->success ? RefundStatus::Processed : RefundStatus::Failed,
                'provider_refund_id' => $refundResult->providerRefundId,
                'created_at' => now(),
            ]);

            if ($refundResult->success) {
                // Update order financial status
                $newTotalRefunded = $totalRefunded + $amount;

                if ($newTotalRefunded >= $order->total_amount) {
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
                if ($restock && $lines !== null) {
                    foreach ($lines as $lineId => $qty) {
                        $orderLine = $order->lines()->with('variant.inventoryItem')->find($lineId);
                        if ($orderLine && $orderLine->variant && $orderLine->variant->inventoryItem) {
                            $this->inventoryService->restock($orderLine->variant->inventoryItem, $qty);
                        }
                    }
                } elseif ($restock && $lines === null) {
                    // Full refund restock
                    foreach ($order->lines()->with('variant.inventoryItem')->get() as $orderLine) {
                        if ($orderLine->variant && $orderLine->variant->inventoryItem) {
                            $this->inventoryService->restock($orderLine->variant->inventoryItem, $orderLine->quantity);
                        }
                    }
                }

                OrderRefunded::dispatch($order, $refund);
            }

            return $refund;
        });
    }
}
