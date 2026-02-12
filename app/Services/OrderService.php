<?php

namespace App\Services;

use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderCancelled;
use App\Models\Order;

class OrderService
{
    public function __construct(
        private InventoryService $inventoryService,
    ) {}

    public function cancel(Order $order, ?string $reason = null): void
    {
        $order->load('lines.variant.inventoryItem', 'payments');

        // Release inventory for orders with reserved stock (pending bank transfers)
        if ($order->financial_status === FinancialStatus::Pending) {
            foreach ($order->lines as $line) {
                if ($line->variant?->inventoryItem) {
                    $this->inventoryService->release($line->variant->inventoryItem, $line->quantity);
                }
            }
        }

        // Void any pending payments
        foreach ($order->payments as $payment) {
            if ($payment->status === PaymentStatus::Pending) {
                $payment->update(['status' => PaymentStatus::Failed]);
            }
        }

        $order->update([
            'status' => OrderStatus::Cancelled,
            'financial_status' => $order->financial_status === FinancialStatus::Pending
                ? FinancialStatus::Voided
                : $order->financial_status,
            'cancelled_at' => now(),
            'cancel_reason' => $reason,
        ]);

        OrderCancelled::dispatch($order);
    }
}
