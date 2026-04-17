<?php

namespace App\Services;

use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderPaid;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class BankTransferService
{
    public function __construct(
        private InventoryService $inventoryService,
        private OrderService $orderService,
    ) {}

    /**
     * Confirm a bank transfer payment has been received.
     */
    public function confirmPayment(Order $order): Order
    {
        if ($order->payment_method !== PaymentMethod::BankTransfer) {
            throw new \InvalidArgumentException('Only bank transfer orders can be confirmed this way.');
        }

        if ($order->financial_status !== FinancialStatus::Pending) {
            throw new \InvalidArgumentException('Payment has already been confirmed or is not pending.');
        }

        /** @var Order $result */
        $result = DB::transaction(function () use ($order) {
            // Update payment status
            $order->payments()
                ->where('status', PaymentStatus::Pending->value)
                ->update(['status' => PaymentStatus::Captured]);

            // Update order statuses
            $order->update([
                'financial_status' => FinancialStatus::Paid,
                'status' => OrderStatus::Paid,
            ]);

            // Commit reserved inventory
            $order->loadMissing('lines.variant.inventoryItem');
            foreach ($order->lines as $line) {
                if ($line->variant?->inventoryItem) {
                    $this->inventoryService->commit($line->variant->inventoryItem, $line->quantity);
                }
            }

            // Auto-fulfill digital products
            $this->orderService->autoFulfillDigitalProducts($order->refresh());

            OrderPaid::dispatch($order->refresh());

            return $order->refresh();
        });

        return $result;
    }
}
