<?php

namespace App\Jobs;

use App\Enums\FinancialStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CancelUnpaidBankTransferOrders implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(OrderService $orders): void
    {
        Order::withoutGlobalScopes()
            ->with('store.settings')
            ->where('payment_method', PaymentMethod::BankTransfer->value)
            ->where('financial_status', FinancialStatus::Pending->value)
            ->whereNotNull('placed_at')
            ->lazyById()
            ->each(function (Order $order) use ($orders): void {
                $cancelDays = max(1, (int) data_get($order->store?->settings?->settings_json, 'bank_transfer_cancel_days', 7));

                if ($order->placed_at->lessThanOrEqualTo(now()->subDays($cancelDays))) {
                    $orders->cancelUnpaidBankTransferOrder($order);
                }
            });
    }
}
