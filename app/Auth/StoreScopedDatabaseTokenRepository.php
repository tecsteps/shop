<?php

namespace App\Auth;

use App\Models\Store;
use Illuminate\Auth\Passwords\DatabaseTokenRepository;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Support\Carbon;

class StoreScopedDatabaseTokenRepository extends DatabaseTokenRepository
{
    public function create(CanResetPassword $user): string
    {
        $this->deleteExisting($user);
        $token = $this->createNewToken();

        $this->getTable()->insert([
            'store_id' => $this->currentStoreId(),
            'email' => $user->getEmailForPasswordReset(),
            'token' => $this->hasher->make($token),
            'created_at' => new Carbon,
        ]);

        return $token;
    }

    public function exists(CanResetPassword $user, $token): bool
    {
        $record = (array) $this->getTable()
            ->where('store_id', $this->currentStoreId())
            ->where('email', $user->getEmailForPasswordReset())
            ->first();

        return $record !== []
            && ! $this->tokenExpired($record['created_at'])
            && $this->hasher->check($token, $record['token']);
    }

    public function recentlyCreatedToken(CanResetPassword $user): bool
    {
        $record = (array) $this->getTable()
            ->where('store_id', $this->currentStoreId())
            ->where('email', $user->getEmailForPasswordReset())
            ->first();

        return $record !== [] && $this->tokenRecentlyCreated($record['created_at']);
    }

    public function delete(CanResetPassword $user): void
    {
        $this->deleteExisting($user);
    }

    protected function deleteExisting(CanResetPassword $user): int
    {
        return $this->getTable()
            ->where('store_id', $this->currentStoreId())
            ->where('email', $user->getEmailForPasswordReset())
            ->delete();
    }

    private function currentStoreId(): int
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        if (! $store instanceof Store) {
            throw new \LogicException('A store context is required for customer password resets.');
        }

        return (int) $store->id;
    }
}
