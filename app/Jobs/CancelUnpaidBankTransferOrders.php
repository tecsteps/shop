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
     * Default number of days an unpaid bank transfer order is held
     * (spec 05 section 10.8), overridable per store via the
     * "bank_transfer_cancel_days" key in store_settings.settings_json.
     */
    public const int DEFAULT_CANCEL_DAYS = 7;

    /**
     * Cancel bank transfer orders whose payment never arrived, releasing the
     * reserved inventory and voiding the pending payment.
     */
    public function handle(OrderService $orderService): void
    {
        Order::query()
            ->withoutGlobalScopes()
            ->where('payment_method', PaymentMethod::BankTransfer)
            ->where('financial_status', FinancialStatus::Pending)
            ->whereNotNull('placed_at')
            ->with('store.settings')
            ->each(function (Order $order) use ($orderService): void {
                $cancelDays = (int) ($order->store->settings?->settings_json['bank_transfer_cancel_days']
                    ?? self::DEFAULT_CANCEL_DAYS);

                if ($order->placed_at->lt(now()->subDays($cancelDays))) {
                    $orderService->cancel($order, 'Unpaid bank transfer timeout');
                }
            });
    }
}
