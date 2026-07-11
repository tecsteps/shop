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
    ->in('Feature');

pest()->extend(Tests\BrowserTestCase::class)
    ->use(Illuminate\Foundation\Testing\DatabaseTruncation::class)
    ->in('Browser');

pest()->browser()->withHost('127.0.0.1');

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

function something()
{
    // ..
}

function seedBrowserShop(Tests\BrowserTestCase $test): void
{
    $test->seed(Database\Seeders\DatabaseSeeder::class);

    App\Models\StoreDomain::query()->create([
        'store_id' => App\Models\Store::query()->where('handle', 'acme-fashion')->valueOrFail('id'),
        'hostname' => '127.0.0.1',
        'type' => App\Enums\StoreDomainType::Storefront,
        'is_primary' => false,
        'tls_mode' => 'managed',
    ]);

    Illuminate\Support\Facades\Cache::forget('store_domain:127.0.0.1');
}

function loginBrowserAdmin(): mixed
{
    return visit('/admin/login')
        ->fill('email', 'admin@acme.test')
        ->fill('password', 'password')
        ->click('form button[type="submit"]')
        ->waitForText('Dashboard');
}
