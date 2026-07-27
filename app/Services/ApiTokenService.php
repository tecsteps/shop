<?php

namespace App\Services;

use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Str;

/**
 * Issues Sanctum personal access tokens with the "shop_" prefix
 * (spec 06 §1.3). Only the SHA-256 hash is persisted; Sanctum matches
 * incoming bearer tokens by hashing them the same way.
 */
class ApiTokenService
{
    /**
     * Create a token and return its plain-text value. The plain value is
     * shown exactly once; only its SHA-256 hash is stored.
     *
     * @param  list<string>  $abilities
     */
    public function create(User $user, string $name, array $abilities, ?DateTimeInterface $expiresAt = null): string
    {
        $plainTextToken = 'shop_'.Str::random(40);

        $user->tokens()->create([
            'name' => $name,
            'token' => hash('sha256', $plainTextToken),
            'abilities' => $abilities,
            'expires_at' => $expiresAt ?? now()->addYear(),
        ]);

        return $plainTextToken;
    }

    /**
     * Revoke one of the user's tokens (deletes the row). Returns false
     * when the token does not belong to the user.
     */
    public function revoke(User $user, int $tokenId): bool
    {
        return $user->tokens()->whereKey($tokenId)->delete() > 0;
    }
}
