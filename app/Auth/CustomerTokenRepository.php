<?php

namespace App\Auth;

use Illuminate\Auth\Passwords\DatabaseTokenRepository;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Token repository for the "customers" password broker. The
 * customer_password_reset_tokens table has a composite (store_id, email)
 * primary key, so every query is additionally scoped by the store that
 * the ResolveStore middleware bound to the container (spec 06 §1.2).
 */
class CustomerTokenRepository extends DatabaseTokenRepository
{
    /**
     * Only look at records of the current store when deciding whether a
     * reset was recently requested (per-store 60 second throttle).
     */
    public function recentlyCreatedToken(CanResetPasswordContract $user)
    {
        $record = (array) $this->getTable()
            ->where('store_id', $this->storeId())
            ->where('email', $user->getEmailForPasswordReset())
            ->first();

        return $record && $this->tokenRecentlyCreated($record['created_at']);
    }

    /**
     * Determine if a token record exists and is valid for this store.
     *
     * @param  string  $token
     */
    public function exists(CanResetPasswordContract $user, #[\SensitiveParameter] $token)
    {
        $record = (array) $this->getTable()
            ->where('store_id', $this->storeId())
            ->where('email', $user->getEmailForPasswordReset())
            ->first();

        return $record &&
               ! $this->tokenExpired($record['created_at']) &&
                 $this->hasher->check($token, $record['token']);
    }

    /**
     * Delete only the current store's tokens for the user.
     */
    protected function deleteExisting(CanResetPasswordContract $user)
    {
        return $this->getTable()
            ->where('store_id', $this->storeId())
            ->where('email', $user->getEmailForPasswordReset())
            ->delete();
    }

    /**
     * Build the record payload, including the store the reset belongs to.
     *
     * @param  string  $email
     * @param  string  $token
     * @return array<string, mixed>
     */
    protected function getPayload($email, #[\SensitiveParameter] $token)
    {
        return [
            'store_id' => $this->storeId(),
            'email' => $email,
            'token' => $this->hasher->make($token),
            'created_at' => new Carbon,
        ];
    }

    /**
     * The id of the store bound to the container by ResolveStore.
     */
    private function storeId(): int
    {
        if (! app()->bound('current_store')) {
            throw new RuntimeException('Customer password resets require a resolved store.');
        }

        return (int) app('current_store')->getKey();
    }
}
