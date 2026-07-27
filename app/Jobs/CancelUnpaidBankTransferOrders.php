<?php

namespace App\Jobs;

use App\Enums\FinancialStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Store;
use App\Services\OrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Cancel bank transfer orders that remain unpaid past the configured timeout
 * (spec 05 §10.8). Runs daily; the timeout is read per store from
 * store_settings.settings_json key bank_transfer_cancel_days (default 7).
 */
class CancelUnpaidBankTransferOrders implements ShouldQueue
{
    use Dispatchable, Queueable;

    /**
     * Execute the job.
     */
    public function handle(OrderService $orders): void
    {
        $candidates = Order::query()
            ->where('payment_method', PaymentMethod::BankTransfer->value)
            ->where('financial_status', FinancialStatus::Pending->value);

        foreach ($candidates->pluck('store_id')->unique() as $storeId) {
            $store = Store::find($storeId);
            $days = (int) ($store?->settings?->settings_json['bank_transfer_cancel_days'] ?? 7);

            Order::query()
                ->where('store_id', $storeId)
                ->where('payment_method', PaymentMethod::BankTransfer->value)
                ->where('financial_status', FinancialStatus::Pending->value)
                ->where('placed_at', '<', now()->subDays($days))
                ->chunkById(100, function ($stale) use ($orders): void {
                    foreach ($stale as $order) {
                        $orders->cancel($order, 'bank_transfer_unpaid_timeout');
                    }
                });
        }
    }
}
