<?php

namespace App\Providers;

use App\Contracts\PaymentProvider;
use App\Services\Payment\MockPaymentProvider;
use Illuminate\Support\ServiceProvider;

/**
 * Binds the payment abstraction to the in-process mock PSP.
 *
 * The platform uses a mock payment provider (no real gateway), so the
 * {@see PaymentProvider} contract resolves to {@see MockPaymentProvider}
 * everywhere it is injected.
 */
class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentProvider::class, MockPaymentProvider::class);
    }
}
