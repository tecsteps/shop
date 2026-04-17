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
        $days = config('shop.bank_transfer_expiry_days', 7);

        $orders = Order::query()
            ->where('payment_method', PaymentMethod::BankTransfer)
            ->where('financial_status', FinancialStatus::Pending)
            ->where('placed_at', '<', now()->subDays($days))
            ->get();

        foreach ($orders as $order) {
            $orderService->cancel($order, 'Auto-cancelled: bank transfer payment not received within '.$days.' days.');
        }
    }
}
