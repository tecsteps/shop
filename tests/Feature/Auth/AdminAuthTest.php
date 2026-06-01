<?php

use App\Livewire\Admin\Auth\Login;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext(['bind' => false]);
});

it('renders the admin login page', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('Log in');
});

it('authenticates an admin user with valid credentials', function () {
    $user = User::factory()->create(['password_hash' => Hash::make('secret-password')]);
    $this->context['store']->users()->attach($user->id, ['role' => 'admin']);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'secret-password')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect('/admin');

    expect(auth('web')->check())->toBeTrue();
});

it('rejects invalid credentials', function () {
    $user = User::factory()->create(['password_hash' => Hash::make('secret-password')]);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email');

    expect(auth('web')->check())->toBeFalse();
});

it('does not reveal whether email or password is incorrect', function () {
    Livewire::test(Login::class)
        ->set('email', 'nobody@example.test')
        ->set('password', 'whatever')
        ->call('login')
        ->assertHasErrors(['email' => 'Invalid credentials']);
});

it('rate limits login attempts', function () {
    $user = User::factory()->create(['password_hash' => Hash::make('secret-password')]);

    foreach (range(1, 5) as $attempt) {
        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors('email');
    }

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email')
        ->assertSee('Too many attempts', false);
});

it('regenerates session on successful login', function () {
    $user = User::factory()->create(['password_hash' => Hash::make('secret-password')]);
    $this->context['store']->users()->attach($user->id, ['role' => 'admin']);

    $before = session()->getId();

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'secret-password')
        ->call('login');

    expect(session()->getId())->not->toBe($before);
});

it('logs out and invalidates session', function () {
    $user = User::factory()->create();
    actingAsAdmin($user, $this->context['store']);

    $this->post('/admin/logout')->assertRedirect(route('admin.login'));

    expect(auth('web')->check())->toBeFalse();
});

it('redirects unauthenticated users to login', function () {
    actingAsAdmin($this->context['owner'], $this->context['store']);
    auth('web')->logout();

    $this->get('/admin')->assertRedirect('/admin/login');
});

it('supports remember me functionality', function () {
    $user = User::factory()->create(['password_hash' => Hash::make('secret-password')]);
    $this->context['store']->users()->attach($user->id, ['role' => 'admin']);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'secret-password')
        ->set('remember', true)
        ->call('login');

    expect($user->fresh()->remember_token)->not->toBeNull();
});

it('records last_login_at on successful login', function () {
    $user = User::factory()->create([
        'password_hash' => Hash::make('secret-password'),
        'last_login_at' => null,
    ]);
    $this->context['store']->users()->attach($user->id, ['role' => 'admin']);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'secret-password')
        ->call('login');

    expect($user->fresh()->last_login_at)->not->toBeNull();
});

afterEach(function () {
    RateLimiter::clear('');
});
