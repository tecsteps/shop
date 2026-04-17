<?php

namespace App\Jobs;

use App\Enums\CheckoutStatus;
use App\Models\Checkout;
use App\Services\CheckoutService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ExpireAbandonedCheckouts implements ShouldQueue
{
    use Queueable;

    public function handle(CheckoutService $checkoutService): void
    {
        $checkouts = Checkout::query()
            ->withoutGlobalScopes()
            ->whereNotIn('status', [
                CheckoutStatus::Completed->value,
                CheckoutStatus::Expired->value,
            ])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        foreach ($checkouts as $checkout) {
            try {
                $checkoutService->expireCheckout($checkout);
            } catch (\Throwable $e) {
                Log::error('Failed to expire checkout', [
                    'checkout_id' => $checkout->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($checkouts->isNotEmpty()) {
            Log::info('Expired abandoned checkouts', ['count' => $checkouts->count()]);
        }
    }
}
