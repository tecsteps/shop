<?php

namespace App\Jobs;

use App\Enums\FinancialStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CancelUnpaidBankTransferOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(OrderService $orderService): void
    {
        $orders = Order::withoutGlobalScopes()
            ->where('payment_method', PaymentMethod::BankTransfer)
            ->where('financial_status', FinancialStatus::Pending)
            ->where('placed_at', '<', now()->subDays($this->getCancelDays()))
            ->get();

        foreach ($orders as $order) {
            $orderService->cancel($order, 'Auto-cancelled: bank transfer payment not received.');
        }
    }

    protected function getCancelDays(): int
    {
        return 7;
    }
}
