<?php

namespace App\Services;

use App\Models\PersonalAccessToken;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ApiTokenService
{
    /** @var list<string> */
    private const ALLOWED_ABILITIES = [
        'read-products',
        'write-products',
        'read-orders',
        'write-orders',
        'read-customers',
        'write-customers',
        'read-collections',
        'write-collections',
        'read-discounts',
        'write-discounts',
        'read-analytics',
        'read-settings',
        'write-settings',
        'read-themes',
        'write-themes',
        'read-content',
        'write-content',
        'manage-platform',
    ];

    /** @param list<string> $abilities
     * @return array{token: PersonalAccessToken, plain_text_token: string}
     */
    public function create(User $user, Store $store, string $name, array $abilities, ?\DateTimeInterface $expiresAt = null): array
    {
        abort_unless($user->stores()->whereKey($store->getKey())->exists(), 403);

        foreach ($abilities as $ability) {
            if (! is_string($ability) || ! in_array($ability, self::ALLOWED_ABILITIES, true)) {
                throw new InvalidArgumentException('One or more API token abilities are not supported.');
            }
        }

        $plainTextToken = 'shop_'.Str::random(64);
        $token = $user->tokens()->create([
            'store_id' => $store->getKey(),
            'name' => $name,
            'token' => hash('sha256', $plainTextToken),
            'abilities' => $abilities,
            'expires_at' => $expiresAt ?? now()->addYear(),
        ]);

        return ['token' => $token, 'plain_text_token' => $plainTextToken];
    }

    public function revoke(PersonalAccessToken $token, Store $store): void
    {
        abort_unless((int) $token->store_id === (int) $store->getKey(), 404);

        $token->delete();
    }

    /** @return list<string> */
    public function availableAbilities(): array
    {
        return array_values(array_diff(self::ALLOWED_ABILITIES, ['manage-platform']));
    }
}
