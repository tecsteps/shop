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
        private PaymentProvider $paymentProvider,
        private InventoryService $inventoryService,
    ) {}

    public function create(Order $order, Payment $payment, int $amount, ?string $reason = null, bool $restock = false): Refund
    {
        $totalRefunded = $order->refunds()->sum('amount');
        $refundable = $payment->amount - $totalRefunded;

        if ($amount > $refundable) {
            throw new \InvalidArgumentException("Refund amount ({$amount}) exceeds refundable amount ({$refundable}).");
        }

        return DB::transaction(function () use ($order, $payment, $amount, $reason, $restock, $totalRefunded) {
            $providerResult = $this->paymentProvider->refund($payment, $amount);

            $refund = Refund::create([
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'amount' => $amount,
                'currency' => $order->currency,
                'reason' => $reason,
                'status' => $providerResult->success ? RefundStatus::Processed : RefundStatus::Failed,
                'restock' => $restock,
                'provider_refund_id' => $providerResult->providerRefundId,
                'processed_at' => $providerResult->success ? now() : null,
                'created_at' => now(),
            ]);

            if ($providerResult->success) {
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

                if ($restock) {
                    $order->load('lines.variant.inventoryItem');
                    foreach ($order->lines as $line) {
                        if ($line->variant && $line->variant->inventoryItem) {
                            $this->inventoryService->restock($line->variant->inventoryItem, $line->quantity);
                        }
                    }
                }

                OrderRefunded::dispatch($order, $refund);
            }

            return $refund;
        });
    }
}
