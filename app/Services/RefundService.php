<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\RefundStatus;
use App\Events\OrderRefunded;
use App\Exceptions\RefundExceedsPaymentException;
use App\Models\Order;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;

class RefundService
{
    public function __construct(
        private PaymentProvider $paymentProvider,
        private InventoryService $inventoryService,
    ) {}

    /**
     * Create a refund for an order.
     */
    public function create(
        Order $order,
        ?int $amount = null,
        ?string $reason = null,
        bool $restock = false,
    ): Refund {
        $order->loadMissing(['payments', 'refunds', 'lines.variant.inventoryItem']);

        $capturedPayment = $order->payments
            ->where('status', \App\Enums\PaymentStatus::Captured)
            ->first();

        if (! $capturedPayment) {
            throw new \RuntimeException('No captured payment found for this order.');
        }

        /** @var int $totalRefunded */
        $totalRefunded = $order->refunds
            ->where('status', RefundStatus::Processed)
            ->sum('amount');

        $refundable = $order->total_amount - $totalRefunded;

        $refundAmount = $amount ?? $refundable;

        if ($refundAmount > $refundable) {
            throw new RefundExceedsPaymentException($refundAmount, $refundable);
        }

        /** @var Refund $result */
        $result = DB::transaction(function () use ($order, $capturedPayment, $refundAmount, $totalRefunded, $reason, $restock) {
            $providerResult = $this->paymentProvider->refund($capturedPayment, $refundAmount);

            /** @var Refund $refund */
            $refund = Refund::query()->create([
                'order_id' => $order->id,
                'payment_id' => $capturedPayment->id,
                'amount' => $refundAmount,
                'reason' => $reason,
                'status' => $providerResult->success ? RefundStatus::Processed : RefundStatus::Failed,
                'provider_refund_id' => $providerResult->providerRefundId,
                'created_at' => now(),
            ]);

            if ($providerResult->success) {
                $totalRefundedNow = $totalRefunded + $refundAmount;

                if ($totalRefundedNow >= $order->total_amount) {
                    $order->update([
                        'financial_status' => FinancialStatus::Refunded,
                        'status' => OrderStatus::Refunded,
                    ]);
                } else {
                    $order->update([
                        'financial_status' => FinancialStatus::PartiallyRefunded,
                    ]);
                }

                // Restock if requested
                if ($restock) {
                    foreach ($order->lines as $line) {
                        if ($line->variant?->inventoryItem) {
                            $this->inventoryService->restock($line->variant->inventoryItem, $line->quantity);
                        }
                    }
                }

                OrderRefunded::dispatch($order->refresh());
            }

            return $refund;
        });

        return $result;
    }
}
