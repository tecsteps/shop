<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * Platform (Phase 8-10) cross-cutting registrations that must not live in the
 * foundation-owned AppServiceProvider.
 *
 * Currently this re-registers the `checkout` rate limiter so it tolerates the
 * sessionless storefront REST API: the limiter is keyed by session id on the
 * web checkout, but `/api/storefront/v1/checkouts/*` requests carry no session,
 * so we fall back to the client IP. This provider boots after AppServiceProvider
 * (see bootstrap/providers.php), so this definition overrides the foundation's.
 */
class PlatformServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('checkout', fn (Request $request) => Limit::perMinute(10)->by(
            $request->hasSession() ? $request->session()->getId() : $request->ip(),
        ));
    }
}
