<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\StoreSettings;
use App\Services\OrderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CancelUnpaidBankTransferOrders implements ShouldQueue
{
    use Queueable;

    public function handle(OrderService $orders): void
    {
        $settingsByStore = StoreSettings::query()->get()->keyBy('store_id');
        $pendingOrders = Order::withoutGlobalScopes()
            ->where('payment_method', 'bank_transfer')
            ->where('financial_status', 'pending')
            ->whereNotNull('placed_at')
            ->orderBy('id')
            ->get();

        foreach ($pendingOrders as $order) {
            $settings = $settingsByStore->get($order->store_id)?->settings_json ?? [];
            $expiryDays = max(1, (int) ($settings['bank_transfer_cancel_days'] ?? 7));

            if ($order->placed_at->lte(now()->subDays($expiryDays))) {
                $orders->cancelPending($order);
            }
        }
    }
}
