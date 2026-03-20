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

class RefundService
{
    public function __construct(
        protected PaymentProvider $paymentProvider,
        protected InventoryService $inventoryService,
    ) {}

    public function create(Order $order, Payment $payment, int $amount, ?string $reason = null, bool $restock = false): Refund
    {
        return DB::transaction(function () use ($order, $payment, $amount, $reason, $restock) {
            $existingRefunds = $order->refunds()->sum('amount');
            $refundable = $order->total_amount - $existingRefunds;

            if ($amount > $refundable) {
                throw new \RuntimeException("Refund amount ({$amount}) exceeds refundable amount ({$refundable}).");
            }

            $refundResult = $this->paymentProvider->refund($payment, $amount);

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
                $totalRefunded = $existingRefunds + $amount;

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

                if ($restock) {
                    $order->load('lines.variant.inventoryItem');
                    foreach ($order->lines as $line) {
                        if ($line->variant && $line->variant->inventoryItem) {
                            $this->inventoryService->restock($line->variant->inventoryItem, $line->quantity);
                        }
                    }
                }

                OrderRefunded::dispatch($order->fresh());
            }

            return $refund;
        });
    }
}
