<?php

namespace App\Services;

use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Support\Str;

class ApiTokenService
{
    /** @param list<string> $abilities
     * @return array{token: PersonalAccessToken, plain_text_token: string}
     */
    public function create(User $user, string $name, array $abilities, ?\DateTimeInterface $expiresAt = null): array
    {
        $plainTextToken = Str::random(64);
        $token = $user->tokens()->create([
            'name' => $name,
            'token' => hash('sha256', $plainTextToken),
            'abilities' => $abilities,
            'expires_at' => $expiresAt,
        ]);

        return ['token' => $token, 'plain_text_token' => $plainTextToken];
    }

    public function revoke(PersonalAccessToken $token, User $user): void
    {
        abort_unless($token->tokenable_type === $user->getMorphClass() && (int) $token->tokenable_id === (int) $user->id, 404);
        $token->delete();
    }
}
