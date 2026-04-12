<?php

use App\Livewire\Admin\Auth\Login;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    RateLimiter::clear('login:admin@example.test|127.0.0.1');
});

it('allows an admin to log in with valid credentials', function (): void {
    $user = User::factory()->create([
        'email' => 'admin@example.test',
        'password' => Hash::make('secret-pass'),
    ]);

    $store = Store::factory()->create();
    $store->users()->attach($user, ['role' => 'owner']);

    Livewire::test(Login::class)
        ->set('email', 'admin@example.test')
        ->set('password', 'secret-pass')
        ->call('login')
        ->assertRedirect('/admin');

    expect(auth('web')->id())->toBe($user->id)
        ->and(session('current_store_id'))->toBe($store->id);
});

it('rejects login with wrong password', function (): void {
    User::factory()->create([
        'email' => 'admin@example.test',
        'password' => Hash::make('secret-pass'),
    ]);

    Livewire::test(Login::class)
        ->set('email', 'admin@example.test')
        ->set('password', 'nope')
        ->call('login')
        ->assertHasErrors('email');

    expect(auth('web')->check())->toBeFalse();
});

it('rate limits login after 5 failed attempts', function (): void {
    User::factory()->create([
        'email' => 'admin@example.test',
        'password' => Hash::make('secret-pass'),
    ]);

    $component = Livewire::test(Login::class)
        ->set('email', 'admin@example.test')
        ->set('password', 'wrong');

    for ($i = 0; $i < 5; $i++) {
        $component->call('login');
    }

    $component->set('password', 'secret-pass')
        ->call('login')
        ->assertHasErrors('email');

    expect(auth('web')->check())->toBeFalse();
});
