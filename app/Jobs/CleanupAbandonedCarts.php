<?php

namespace App\Jobs;

use App\Enums\CartStatus;
use App\Models\Cart;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CleanupAbandonedCarts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $threshold = now()->subDays(14);

        Cart::query()
            ->withoutGlobalScopes()
            ->where('status', CartStatus::Active)
            ->where('updated_at', '<=', $threshold)
            ->update(['status' => CartStatus::Abandoned->value]);
    }
}
