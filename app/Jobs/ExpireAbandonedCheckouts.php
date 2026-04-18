<?php

namespace App\Jobs;

use App\Enums\CheckoutStatus;
use App\Models\Checkout;
use App\Services\CheckoutService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExpireAbandonedCheckouts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(CheckoutService $service): void
    {
        Checkout::query()
            ->withoutGlobalScopes()
            ->whereNotIn('status', [CheckoutStatus::Completed, CheckoutStatus::Expired])
            ->where(function ($query): void {
                $query->where('expires_at', '<=', now())
                    ->orWhere('updated_at', '<=', now()->subHours(24));
            })
            ->cursor()
            ->each(function (Checkout $checkout) use ($service): void {
                $service->expireCheckout($checkout);
            });
    }
}
