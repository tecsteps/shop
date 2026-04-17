<?php

namespace App\Auth;

use App\Models\Customer;
use App\Models\Store;
use Illuminate\Auth\Passwords\DatabaseTokenRepository;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;

class CustomerTokenRepository extends DatabaseTokenRepository
{
    public function create(CanResetPasswordContract $user)
    {
        $this->deleteExisting($user);

        $token = $this->createNewToken();

        $this->getTable()->insert($this->getPayload(
            $user->getEmailForPasswordReset(),
            $token,
            $this->storeIdForUser($user),
        ));

        return $token;
    }

    protected function deleteExisting(CanResetPasswordContract $user): int
    {
        return $this->getTable()
            ->where('email', $user->getEmailForPasswordReset())
            ->where('store_id', $this->storeIdForUser($user))
            ->delete();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getPayload($email, #[\SensitiveParameter] $token, ?int $storeId = null): array
    {
        return [
            'email' => $email,
            'store_id' => $storeId ?? $this->resolveCurrentStoreId(),
            'token' => $this->hasher->make($token),
            'created_at' => new Carbon,
        ];
    }

    public function exists(CanResetPasswordContract $user, #[\SensitiveParameter] $token)
    {
        $record = (array) $this->getTable()
            ->where('email', $user->getEmailForPasswordReset())
            ->where('store_id', $this->storeIdForUser($user))
            ->first();

        return $record !== []
            && ! $this->tokenExpired((string) $record['created_at'])
            && $this->hasher->check($token, (string) $record['token']);
    }

    public function recentlyCreatedToken(CanResetPasswordContract $user)
    {
        $record = (array) $this->getTable()
            ->where('email', $user->getEmailForPasswordReset())
            ->where('store_id', $this->storeIdForUser($user))
            ->first();

        return $record !== [] && $this->tokenRecentlyCreated((string) $record['created_at']);
    }

    public function delete(CanResetPasswordContract $user)
    {
        $this->deleteExisting($user);
    }

    protected function storeIdForUser(CanResetPasswordContract $user): int
    {
        if ($user instanceof Customer && $user->store_id) {
            return (int) $user->store_id;
        }

        return $this->resolveCurrentStoreId();
    }

    protected function resolveCurrentStoreId(): int
    {
        if (app()->bound('current_store')) {
            $store = app('current_store');

            if ($store instanceof Store) {
                return (int) $store->getKey();
            }
        }

        return 0;
    }

    protected function getTable(): Builder
    {
        return $this->connection->table($this->table);
    }
}
