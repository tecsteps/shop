<?php

namespace App\Jobs;

use App\Enums\CheckoutStatus;
use App\Models\Checkout;
use App\Services\CheckoutService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExpireAbandonedCheckouts implements ShouldQueue
{
    use Queueable;

    public function __construct(protected CheckoutService $checkouts) {}

    public function handle(): void
    {
        Checkout::query()
            ->withoutGlobalScopes()
            ->whereNotIn('status', [CheckoutStatus::Completed->value, CheckoutStatus::Expired->value])
            ->where(function ($query): void {
                $query->where('expires_at', '<', now())
                    ->orWhere('updated_at', '<', now()->subHours(24));
            })
            ->orderBy('id')
            ->chunkById(100, function ($expired): void {
                foreach ($expired as $checkout) {
                    $this->checkouts->expireCheckout($checkout);
                }
            });
    }
}
