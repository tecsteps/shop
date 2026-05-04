<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->in('Feature', 'Browser');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * @param  list<string>  $abilities
 */
function adminApiBearerToken(\App\Models\Store $store, array $abilities, ?\App\Models\User $user = null): string
{
    return adminApiToken($store, $abilities, $user)['plain_text'];
}

/**
 * @param  list<string>  $abilities
 * @return array{token: \App\Models\PersonalAccessToken, plain_text: string}
 */
function adminApiToken(\App\Models\Store $store, array $abilities, ?\App\Models\User $user = null): array
{
    $user ??= $store->users()->wherePivot('role', 'owner')->first()
        ?? $store->users()->first();

    if (! $user instanceof \App\Models\User) {
        $user = \App\Models\User::factory()->create(['email_verified_at' => now()]);
        $store->users()->attach($user->getKey(), [
            'role' => \App\Enums\StoreUserRole::Owner->value,
            'created_at' => now(),
        ]);
    }

    return app(\App\Services\WebhookService::class)
        ->createApiToken($store, 'Test API token', $abilities, $user);
}
