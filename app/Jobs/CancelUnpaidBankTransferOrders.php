<?php

namespace App\Jobs;

use App\Enums\FinancialStatus;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CancelUnpaidBankTransferOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function handle(OrderService $orders): void
    {
        $days = (int) config('shop.bank_transfer_expiry_days', 7);
        Order::withoutGlobalScopes()->where('payment_method', 'bank_transfer')->where('financial_status', FinancialStatus::Pending)->where('placed_at', '<', now()->subDays($days))->with('lines.variant.inventory')->each(fn (Order $order): mixed => $orders->cancel($order, 'Bank transfer payment expired.'));
    }
}
