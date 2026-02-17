<?php

namespace App\Jobs;

use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderCancelled;
use App\Models\Order;
use App\Services\InventoryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CancelUnpaidBankTransferOrders implements ShouldQueue
{
    use Queueable;

    public function handle(InventoryService $inventoryService): void
    {
        $orders = Order::query()
            ->withoutGlobalScopes()
            ->where('payment_method', PaymentMethod::BankTransfer->value)
            ->where('financial_status', FinancialStatus::Pending->value)
            ->where('placed_at', '<', now()->subDays(7))
            ->get();

        foreach ($orders as $order) {
            // Release reserved inventory
            $order->load('lines.variant.inventoryItem');
            foreach ($order->lines as $line) {
                if ($line->variant && $line->variant->inventoryItem) {
                    $inventoryService->release($line->variant->inventoryItem, $line->quantity);
                }
            }

            $order->update([
                'status' => OrderStatus::Cancelled,
                'financial_status' => FinancialStatus::Voided,
                'cancel_reason' => 'Unpaid bank transfer - auto-cancelled after 7 days',
                'cancelled_at' => now(),
            ]);

            $order->payments()
                ->where('status', PaymentStatus::Pending->value)
                ->update(['status' => PaymentStatus::Failed->value]);

            OrderCancelled::dispatch($order);
        }
    }
}
