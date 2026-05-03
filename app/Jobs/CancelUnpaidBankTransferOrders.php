<?php

namespace App\Jobs;

use App\Enums\FinancialStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Store;
use App\Models\StoreSettings;
use App\Services\OrderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CancelUnpaidBankTransferOrders implements ShouldQueue
{
    use Queueable;

    /**
     * @var list<int>
     */
    public array $backoff = [1, 5, 10];

    public int $tries = 3;

    /**
     * Execute the job.
     */
    public function handle(OrderService $orders): void
    {
        Store::query()
            ->select('id')
            ->lazyById()
            ->each(function (Store $store) use ($orders): void {
                $cutoff = now()->subDays($this->cancelDays($store));

                Order::withoutGlobalScopes()
                    ->where('store_id', $store->id)
                    ->where('payment_method', PaymentMethod::BankTransfer->value)
                    ->where('financial_status', FinancialStatus::Pending->value)
                    ->where('placed_at', '<', $cutoff)
                    ->lazyById()
                    ->each(fn (Order $order): mixed => $orders->cancel($order, 'Bank transfer payment was not received before the cutoff.'));
            });
    }

    private function cancelDays(Store $store): int
    {
        $settings = StoreSettings::query()->find($store->id)?->settings_json ?? [];

        return max(1, (int) ($settings['bank_transfer_cancel_days'] ?? 7));
    }
}
