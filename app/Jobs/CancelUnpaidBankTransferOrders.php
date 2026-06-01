<?php

namespace App\Jobs;

use App\Enums\FinancialStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

/**
 * Cancels bank-transfer orders that remain unpaid beyond the configured window
 * (default 7 days), releasing their reserved inventory. Runs daily.
 */
class CancelUnpaidBankTransferOrders implements ShouldQueue
{
    use Queueable;

    public function handle(OrderService $orders): void
    {
        $cutoff = Carbon::now()->subDays((int) config('shop.bank_transfer_cancel_days', 7));

        Order::query()
            ->withoutGlobalScopes()
            ->where('payment_method', PaymentMethod::BankTransfer->value)
            ->where('financial_status', FinancialStatus::Pending->value)
            ->where('placed_at', '<', $cutoff)
            ->with('lines')
            ->each(function (Order $order) use ($orders): void {
                $orders->cancel($order, 'Bank transfer not received within the allowed window.');
            });
    }
}
