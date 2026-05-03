<?php

namespace App\Services;

use App\Models\ApiToken;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Str;

class ApiTokenService
{
    /**
     * @param  list<string>  $abilities
     * @return array{token: ApiToken, plain_text_token: string}
     */
    public function create(Store $store, User $user, string $name, array $abilities): array
    {
        $plainTextToken = 'shop_'.Str::random(48);

        $token = ApiToken::query()->create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'name' => $name,
            'token_hash' => $this->hash($plainTextToken),
            'abilities_json' => array_values(array_unique($abilities)),
        ]);

        return [
            'token' => $token,
            'plain_text_token' => $plainTextToken,
        ];
    }

    public function findValidToken(string $plainTextToken, string $ability): ?ApiToken
    {
        $token = ApiToken::withoutGlobalScopes()
            ->where('token_hash', $this->hash($plainTextToken))
            ->first();

        if (! $token instanceof ApiToken || $token->isRevoked()) {
            return null;
        }

        if ($ability !== 'any' && ! $token->hasAbility($ability)) {
            return null;
        }

        $token->forceFill(['last_used_at' => now()])->save();

        return $token;
    }

    public function revoke(ApiToken $token): void
    {
        $token->forceFill(['revoked_at' => now()])->save();
    }

    public function hash(string $plainTextToken): string
    {
        return hash('sha256', $plainTextToken);
    }
}
