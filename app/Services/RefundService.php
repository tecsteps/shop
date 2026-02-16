<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Enums\FinancialStatus;
use App\Enums\RefundStatus;
use App\Events\OrderRefunded;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;

class RefundService
{
    public function __construct(
        private PaymentProvider $paymentProvider,
        private InventoryService $inventoryService,
    ) {}

    public function create(Order $order, Payment $payment, int $amount, ?string $reason, bool $restock): Refund
    {
        $result = $this->paymentProvider->refund($payment, $amount);

        $refund = $order->refunds()->create([
            'payment_id' => $payment->id,
            'amount' => $amount,
            'reason' => $reason,
            'status' => $result->success ? RefundStatus::Processed : RefundStatus::Failed,
            'restock' => $restock,
            'processed_at' => $result->success ? now() : null,
        ]);

        if ($result->success) {
            $totalRefunded = $order->refunds()
                ->where('status', RefundStatus::Processed)
                ->sum('amount');

            $financialStatus = $totalRefunded >= $order->total
                ? FinancialStatus::Refunded
                : FinancialStatus::PartiallyRefunded;

            $order->update(['financial_status' => $financialStatus]);

            if ($restock) {
                foreach ($order->lines()->with('variant.inventoryItem')->get() as $line) {
                    if ($line->variant?->inventoryItem) {
                        $this->inventoryService->restock($line->variant->inventoryItem, $line->quantity);
                    }
                }
            }

            event(new OrderRefunded($order));
        }

        return $refund;
    }
}
