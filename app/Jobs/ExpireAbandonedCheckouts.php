<?php

namespace App\Jobs;

use App\Jobs\Concerns\RestoresCurrentStore;
use App\Models\Checkout;
use App\Models\Store;
use App\Services\CheckoutService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ExpireAbandonedCheckouts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use RestoresCurrentStore;

    public int $tries = 3;

    public int $timeout = 300;

    /** @var list<int> */
    public array $backoff = [10, 60, 300];

    public function handle(CheckoutService $service): void
    {
        $this->restoringCurrentStore(function () use ($service): void {
            Checkout::withoutGlobalScopes()
                ->whereNotIn('status', ['completed', 'expired'])
                ->where(function ($query): void {
                    $query->where('expires_at', '<', now())->orWhere('updated_at', '<', now()->subDay());
                })
                ->chunkById(100, fn ($checkouts) => $checkouts->each(function (Checkout $checkout) use ($service): void {
                    app()->instance('current_store', Store::query()->findOrFail($checkout->store_id));
                    $service->expireCheckout($checkout);
                }));
        });
    }
}
