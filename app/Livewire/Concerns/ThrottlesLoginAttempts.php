<?php

namespace App\Livewire\Concerns;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

/**
 * Shared rate limiting for login forms. Both the admin and the customer
 * login use the named "login" limiter (5 attempts per minute, keyed by
 * IP address, spec 06 §4.2).
 */
trait ThrottlesLoginAttempts
{
    /**
     * Abort with 429 when the login rate limit for this IP is exhausted.
     *
     * @throws TooManyRequestsHttpException
     */
    protected function ensureIsNotRateLimited(): void
    {
        $limit = $this->loginLimit();

        if (! RateLimiter::tooManyAttempts($this->throttleKey(), $limit->maxAttempts)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw new TooManyRequestsHttpException($seconds, "Too many attempts. Try again in {$seconds} seconds.");
    }

    /**
     * Record a failed login attempt against the login limiter.
     */
    protected function hitLoginRateLimiter(): void
    {
        RateLimiter::hit($this->throttleKey(), $this->loginLimit()->decaySeconds);
    }

    /**
     * Clear the login rate limiter after a successful login.
     */
    protected function clearLoginRateLimiter(): void
    {
        RateLimiter::clear($this->throttleKey());
    }

    /**
     * The throttle key of the shared "login" rate limiter.
     */
    private function throttleKey(): string
    {
        return (string) $this->loginLimit()->key;
    }

    /**
     * Resolve the named "login" limiter (5 per minute, keyed by IP).
     */
    private function loginLimit(): Limit
    {
        return (RateLimiter::limiter('login'))(request());
    }
}
