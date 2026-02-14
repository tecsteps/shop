<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();

    Route::middleware(['web'])->group(function (): void {
        Route::get('/__admin/auth-smoke', static fn () => response()->noContent())
            ->middleware('auth:web');

        Route::post('/__admin/login-attempt', static fn () => response()->noContent())
            ->middleware('throttle:login');
    });
});

function adminAuthCreateUser(): User
{
    /** @var User $user */
    $user = User::query()->forceCreate([
        'name' => 'Admin User',
        'email' => 'admin-'.Str::lower(Str::random(8)).'@example.test',
        'password_hash' => Hash::make('password'),
        'status' => 'active',
        'email_verified_at' => now(),
    ]);

    return $user;
}

test('admin protected endpoint requires authentication', function (): void {
    $this->get('/__admin/auth-smoke')
        ->assertRedirect(route('login', absolute: false));
});

test('authenticated admin user can access admin protected endpoint', function (): void {
    $admin = adminAuthCreateUser();

    $this->actingAs($admin, 'web')
        ->get('/__admin/auth-smoke')
        ->assertNoContent();
});

test('rate limits repeated admin login attempts to five per minute', function (): void {
    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $this->post('/__admin/login-attempt', [
            'email' => 'admin@example.test',
        ])->assertNoContent();
    }

    $this->post('/__admin/login-attempt', [
        'email' => 'admin@example.test',
    ])->assertStatus(429);
});
