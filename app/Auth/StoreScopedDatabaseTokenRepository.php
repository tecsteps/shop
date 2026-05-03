<?php

namespace App\Auth;

use Illuminate\Auth\Passwords\DatabaseTokenRepository;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class StoreScopedDatabaseTokenRepository extends DatabaseTokenRepository
{
    /**
     * Create a new token record.
     */
    public function create(CanResetPasswordContract $user): string
    {
        $email = $user->getEmailForPasswordReset();

        $this->deleteExisting($user);

        $token = $this->createNewToken();

        $this->getTable()->insert([
            'store_id' => $this->storeId($user),
            'email' => $email,
            'token' => $this->getHasher()->make($token),
            'created_at' => new Carbon,
        ]);

        return $token;
    }

    /**
     * Delete all existing reset tokens from the database.
     */
    protected function deleteExisting(CanResetPasswordContract $user): int
    {
        return $this->queryForUser($user)->delete();
    }

    /**
     * Determine if a token record exists and is valid.
     */
    public function exists(CanResetPasswordContract $user, #[\SensitiveParameter] $token): bool
    {
        $record = (array) $this->queryForUser($user)->first();

        return $record !== []
            && ! $this->tokenExpired($record['created_at'])
            && $this->getHasher()->check($token, $record['token']);
    }

    /**
     * Determine if the given user recently created a password reset token.
     */
    public function recentlyCreatedToken(CanResetPasswordContract $user): bool
    {
        $record = (array) $this->queryForUser($user)->first();

        return $record !== [] && $this->tokenRecentlyCreated($record['created_at']);
    }

    /**
     * Delete a token record by user.
     */
    public function delete(CanResetPasswordContract $user): void
    {
        $this->deleteExisting($user);
    }

    private function queryForUser(CanResetPasswordContract $user): Builder
    {
        return $this->getTable()
            ->where('store_id', $this->storeId($user))
            ->where('email', $user->getEmailForPasswordReset());
    }

    private function storeId(CanResetPasswordContract $user): int
    {
        if (! $user instanceof Model) {
            throw new InvalidArgumentException('Store scoped password reset users must be Eloquent models.');
        }

        $storeId = $user->getAttribute('store_id');

        if (! is_numeric($storeId)) {
            throw new InvalidArgumentException('Store scoped password reset users must have a store_id.');
        }

        return (int) $storeId;
    }
}
