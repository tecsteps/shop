<?php

namespace App\Jobs;

use App\Enums\FinancialStatus;
use App\Models\Order;
use App\Models\StoreSettings;
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
        Order::withoutGlobalScopes()
            ->where('payment_method', 'bank_transfer')
            ->where('financial_status', FinancialStatus::Pending)
            ->with('lines.variant.inventory')
            ->each(function (Order $order) use ($orders): void {
                $settings = StoreSettings::withoutGlobalScopes()->find($order->store_id);
                $days = (int) ($settings?->settings_json['bank_transfer_cancel_days'] ?? config('shop.bank_transfer_expiry_days', 7));

                if ($order->placed_at?->isBefore(now()->subDays($days))) {
                    $orders->cancel($order, 'Bank transfer payment expired.');
                }
            });
    }
}
