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

    public function handle(OrderService $orderService): void
    {
        $orders = Order::withoutGlobalScopes()
            ->where('payment_method', PaymentMethod::BankTransfer->value)
            ->where('financial_status', FinancialStatus::Pending->value)
            ->where('placed_at', '<', now()->subDays(7))
            ->get();

        foreach ($orders as $order) {
            $orderService->cancel($order, 'Auto-cancelled: unpaid bank transfer after 7 days.');
        }
    }
}
