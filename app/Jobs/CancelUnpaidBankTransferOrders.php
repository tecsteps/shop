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
        Order::withoutGlobalScopes()
            ->where('financial_status', FinancialStatus::Pending)
            ->where('payment_method', PaymentMethod::BankTransfer->value)
            ->where('placed_at', '<', now()->subDays(config('shop.bank_transfer_expiry_days', 7)))
            ->each(function (Order $order) use ($orderService): void {
                $orderService->cancel($order, 'Unpaid bank transfer - auto-cancelled');
            });
    }
}
