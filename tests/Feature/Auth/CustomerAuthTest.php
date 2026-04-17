<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();

    Route::middleware(['web'])->group(function (): void {
        Route::get('/__account/auth-smoke', static fn () => response()->noContent())
            ->middleware('auth:customer');

        Route::post('/__account/login-attempt', static fn () => response()->noContent())
            ->middleware('throttle:login');
    });
});

test('customer auth guard is configured', function (): void {
    expect(config('auth.guards.customer'))->toBeArray();
    expect(config('auth.guards.customer.driver'))->toBe('session');
    expect(config('auth.guards.customer.provider'))->toBe('customers');
    expect(config('auth.providers.customers'))->toBeArray();
});

test('customer protected endpoint redirects guests to storefront login', function (): void {
    $this->get('/__account/auth-smoke')
        ->assertRedirect('/account/login');
});

test('rate limits repeated customer login attempts to five per minute', function (): void {
    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $this->post('/__account/login-attempt', [
            'email' => 'customer@example.test',
        ])->assertNoContent();
    }

    $this->post('/__account/login-attempt', [
        'email' => 'customer@example.test',
    ])->assertStatus(429);
});
