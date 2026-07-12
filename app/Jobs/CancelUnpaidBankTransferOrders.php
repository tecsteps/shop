<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class CancelUnpaidBankTransferOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(OrderService $orders): void
    {
        Order::withoutGlobalScopes()
            ->where('payment_method', 'bank_transfer')
            ->where('financial_status', 'pending')
            ->with('store.settings')
            ->chunkById(100, function ($pending) use ($orders): void {
                foreach ($pending as $order) {
                    app()->instance('current_store', $order->store);
                    $days = (int) data_get($order->store?->settings?->settings_json, 'bank_transfer_cancel_days', 7);
                    if ($order->placed_at?->lt(now()->subDays($days))) {
                        $orders->cancel($order, 'Bank transfer payment timeout');
                    }
                }
            });
    }
}
