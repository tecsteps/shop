<?php

declare(strict_types=1);

namespace App\Auth\Passwords;

use Illuminate\Auth\Passwords\DatabaseTokenRepository;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Carbon;
use RuntimeException;

final class StoreScopedTokenRepository extends DatabaseTokenRepository
{
    public function __construct(
        ConnectionInterface $connection,
        HasherContract $hasher,
        string $table,
        string $hashKey,
        int $expires = 3600,
        int $throttle = 60,
    ) {
        parent::__construct(
            $connection,
            $hasher,
            $table,
            $hashKey,
            $expires,
            $throttle,
        );
    }

    public function create(CanResetPasswordContract $user): string
    {
        $email = $user->getEmailForPasswordReset();

        $this->deleteExisting($user);

        $token = $this->createNewToken();

        $this->getTable()->insert($this->buildPayload($email, $token, $this->resolveStoreId($user)));

        return $token;
    }

    protected function deleteExisting(CanResetPasswordContract $user): int
    {
        return $this->getTable()
            ->where('email', $user->getEmailForPasswordReset())
            ->where('store_id', $this->resolveStoreId($user))
            ->delete();
    }

    /**
     * @return array{email: string, store_id: int, token: string, created_at: Carbon}
     */
    protected function buildPayload(string $email, #[\SensitiveParameter] string $token, int $storeId): array
    {
        return [
            'email' => $email,
            'store_id' => $storeId,
            'token' => $this->hasher->make($token),
            'created_at' => new Carbon,
        ];
    }

    public function exists(CanResetPasswordContract $user, #[\SensitiveParameter] $token): bool
    {
        if (! is_string($token) || $token === '') {
            return false;
        }

        $record = $this->recordForUser($user);

        if ($record === null) {
            return false;
        }

        return ! $this->tokenExpired($record['created_at'])
            && $this->hasher->check($token, $record['token']);
    }

    public function recentlyCreatedToken(CanResetPasswordContract $user): bool
    {
        $record = $this->recordForUser($user);

        return $record !== null
            && $this->tokenRecentlyCreated($record['created_at']);
    }

    /**
     * @return array{created_at: string, token: string}|null
     */
    private function recordForUser(CanResetPasswordContract $user): ?array
    {
        $record = $this->getTable()
            ->where('email', $user->getEmailForPasswordReset())
            ->where('store_id', $this->resolveStoreId($user))
            ->first();

        if ($record === null) {
            return null;
        }

        $data = (array) $record;
        $createdAt = $data['created_at'] ?? null;
        $hashedToken = $data['token'] ?? null;

        if (! is_string($createdAt) || ! is_string($hashedToken)) {
            return null;
        }

        return [
            'created_at' => $createdAt,
            'token' => $hashedToken,
        ];
    }

    private function resolveStoreId(CanResetPasswordContract $user): int
    {
        $storeId = $user->store_id ?? null;

        if (! is_int($storeId) && ! is_float($storeId) && ! is_string($storeId)) {
            throw new RuntimeException('Unable to determine store id for customer password reset token.');
        }

        return (int) $storeId;
    }
}
