<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Store;
use App\Notifications\CustomerResetPassword;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class CustomerPasswordResetService
{
    public function sendResetLink(Store $store, string $email): void
    {
        $email = $this->normalizeEmail($email);
        $rateLimitKey = $this->rateLimitKey($store, $email);

        if (RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
            return;
        }

        RateLimiter::hit($rateLimitKey, $this->throttleSeconds());

        $customer = $this->findCustomer($store, $email);

        if (! $customer instanceof Customer) {
            return;
        }

        $token = Str::random(64);

        DB::table($this->table())->updateOrInsert(
            [
                'store_id' => $store->getKey(),
                'email' => $customer->email,
            ],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ],
        );

        $customer->notify(new CustomerResetPassword($token, $store));
    }

    public function reset(Store $store, string $email, string $token, string $password): bool
    {
        $email = $this->normalizeEmail($email);

        $tokenRecord = DB::table($this->table())
            ->where('store_id', $store->getKey())
            ->whereRaw('lower(email) = ?', [$email])
            ->first();

        if (! $tokenRecord || $this->tokenExpired($tokenRecord->created_at) || ! Hash::check($token, $tokenRecord->token)) {
            return false;
        }

        $customer = $this->findCustomer($store, $tokenRecord->email);

        if (! $customer instanceof Customer) {
            $this->deleteToken($store, $tokenRecord->email);

            return false;
        }

        $customer->forceFill([
            'password' => $password,
        ])->save();

        $this->deleteToken($store, $tokenRecord->email);

        event(new \Illuminate\Auth\Events\PasswordReset($customer));

        return true;
    }

    private function findCustomer(Store $store, string $email): ?Customer
    {
        return Customer::withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->whereRaw('lower(email) = ?', [$this->normalizeEmail($email)])
            ->first();
    }

    private function deleteToken(Store $store, string $email): void
    {
        DB::table($this->table())
            ->where('store_id', $store->getKey())
            ->whereRaw('lower(email) = ?', [$this->normalizeEmail($email)])
            ->delete();
    }

    private function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }

    private function tokenExpired(?string $createdAt): bool
    {
        if (! $createdAt) {
            return true;
        }

        return Carbon::parse($createdAt)->addMinutes($this->expireMinutes())->isPast();
    }

    private function rateLimitKey(Store $store, string $email): string
    {
        return 'customer-password-reset:'.$store->getKey().':'.sha1($email);
    }

    private function table(): string
    {
        return config('auth.passwords.customers.table', 'customer_password_reset_tokens');
    }

    private function expireMinutes(): int
    {
        return (int) config('auth.passwords.customers.expire', 60);
    }

    private function throttleSeconds(): int
    {
        return (int) config('auth.passwords.customers.throttle', 60);
    }
}
