<?php

namespace App\Auth;

use App\Models\Store;
use Illuminate\Auth\Passwords\DatabaseTokenRepository;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Support\Carbon;

class StoreScopedTokenRepository extends DatabaseTokenRepository
{
    public function create(CanResetPasswordContract $user): string
    {
        $email = $user->getEmailForPasswordReset();
        $this->deleteExisting($user);
        $token = $this->createNewToken();
        $this->scopedTable()->insert($this->getPayload($email, $token));

        return $token;
    }

    public function exists(CanResetPasswordContract $user, $token): bool
    {
        $record = (array) $this->scopedTable()->where('email', $user->getEmailForPasswordReset())->first();

        return $record !== []
            && ! $this->tokenExpired($record['created_at'])
            && $this->getHasher()->check($token, $record['token']);
    }

    public function recentlyCreatedToken(CanResetPasswordContract $user): bool
    {
        $record = (array) $this->scopedTable()->where('email', $user->getEmailForPasswordReset())->first();

        return $record !== [] && $this->tokenRecentlyCreated($record['created_at']);
    }

    public function delete(CanResetPasswordContract $user): void
    {
        $this->deleteExisting($user);
    }

    public function deleteExpired(): void
    {
        $expiredAt = Carbon::now()->subSeconds($this->expires);
        $this->getTable()->where('created_at', '<', $expiredAt)->delete();
    }

    protected function deleteExisting(CanResetPasswordContract $user): int
    {
        return $this->scopedTable()->where('email', $user->getEmailForPasswordReset())->delete();
    }

    protected function getPayload($email, #[\SensitiveParameter] $token): array
    {
        return array_merge(parent::getPayload($email, $token), ['store_id' => $this->currentStoreId()]);
    }

    private function scopedTable(): \Illuminate\Database\Query\Builder
    {
        return $this->getTable()->where('store_id', $this->currentStoreId());
    }

    private function currentStoreId(): int
    {
        if (! app()->bound('current_store') || ! app('current_store') instanceof Store) {
            throw new \LogicException('A current store is required for customer password reset tokens.');
        }

        return (int) app('current_store')->getKey();
    }
}
