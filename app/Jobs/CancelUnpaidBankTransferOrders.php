<?php

namespace App\Jobs;

use App\Enums\FinancialStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class CancelUnpaidBankTransferOrders implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle(OrderService $orderService): void
    {
        $cancelDays = 7;

        $orders = Order::where('payment_method', PaymentMethod::BankTransfer)
            ->where('financial_status', FinancialStatus::Pending)
            ->where('placed_at', '<', now()->subDays($cancelDays))
            ->cursor();

        foreach ($orders as $order) {
            $orderService->cancel($order, 'Unpaid bank transfer - auto-cancelled after '.$cancelDays.' days');
        }
    }
}
