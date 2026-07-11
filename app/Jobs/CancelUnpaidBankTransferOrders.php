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
     * Create a new job instance.
     */
    public function __construct() {}

    /**
     * Execute the job.
     */
    public function handle(OrderService $orderService): void
    {
        Order::query()
            ->where('payment_method', PaymentMethod::BankTransfer)
            ->where('financial_status', FinancialStatus::Pending)
            ->where('placed_at', '<', now()->subDays(7))
            ->lazyById()
            ->each(fn (Order $order) => $orderService->cancel($order, 'Bank transfer payment timeout.'));
    }
}
