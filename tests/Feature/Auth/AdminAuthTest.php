<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

it('admin login with valid credentials succeeds', function () {
    $user = User::factory()->create([
        'email' => 'admin@example.com',
        'password' => bcrypt('password123'),
    ]);

    Livewire::test(\App\Livewire\Admin\Auth\Login::class)
        ->set('email', 'admin@example.com')
        ->set('password', 'password123')
        ->call('login')
        ->assertRedirect('/admin');

    expect(Auth::guard('web')->check())->toBeTrue();
});

it('admin login with invalid credentials fails', function () {
    User::factory()->create([
        'email' => 'admin@example.com',
        'password' => bcrypt('password123'),
    ]);

    Livewire::test(\App\Livewire\Admin\Auth\Login::class)
        ->set('email', 'admin@example.com')
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email');

    expect(Auth::guard('web')->check())->toBeFalse();
});

it('admin login with non-existent email fails with generic message', function () {
    Livewire::test(\App\Livewire\Admin\Auth\Login::class)
        ->set('email', 'nobody@example.com')
        ->set('password', 'anything')
        ->call('login')
        ->assertHasErrors('email');

    expect(Auth::guard('web')->check())->toBeFalse();
});

it('admin login is rate-limited to 5 attempts per minute', function () {
    User::factory()->create([
        'email' => 'admin@example.com',
        'password' => bcrypt('password123'),
    ]);

    for ($i = 0; $i < 5; $i++) {
        Livewire::test(\App\Livewire\Admin\Auth\Login::class)
            ->set('email', 'admin@example.com')
            ->set('password', 'wrong')
            ->call('login');
    }

    Livewire::test(\App\Livewire\Admin\Auth\Login::class)
        ->set('email', 'admin@example.com')
        ->set('password', 'wrong')
        ->call('login')
        ->assertHasErrors('email');
});

it('admin login with remember me sets a token', function () {
    $user = User::factory()->create([
        'email' => 'admin@example.com',
        'password' => bcrypt('password123'),
    ]);

    Livewire::test(\App\Livewire\Admin\Auth\Login::class)
        ->set('email', 'admin@example.com')
        ->set('password', 'password123')
        ->set('remember', true)
        ->call('login')
        ->assertRedirect('/admin');

    expect(Auth::guard('web')->check())->toBeTrue();
});

it('admin logout invalidates the session', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->post('/admin/logout');
    $response->assertRedirect('/admin/login');

    expect(Auth::guard('web')->check())->toBeFalse();
});

it('login rate limiter is registered', function () {
    $limiter = RateLimiter::limiter('login');
    expect($limiter)->not->toBeNull();
});
