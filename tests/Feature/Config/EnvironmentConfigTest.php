<?php

it('uses sqlite as the default database connection', function () {
    expect(config('database.default'))->toBe('sqlite');
});

it('has WAL mode configured for SQLite', function () {
    expect(config('database.connections.sqlite.journal_mode'))->toBe('WAL');
});

it('has foreign keys enabled for SQLite', function () {
    expect(config('database.connections.sqlite.foreign_key_constraints'))->toBeTrue();
});

it('has busy_timeout set to 5000 for SQLite', function () {
    expect(config('database.connections.sqlite.busy_timeout'))->toBe(5000);
});

it('has synchronous mode set to normal for SQLite', function () {
    expect(config('database.connections.sqlite.synchronous'))->toBe('NORMAL');
});

it('env file specifies file-based cache', function () {
    $envContent = file_get_contents(base_path('.env'));
    expect($envContent)->toContain('CACHE_STORE=file');
});

it('env file specifies file-based sessions', function () {
    $envContent = file_get_contents(base_path('.env'));
    expect($envContent)->toContain('SESSION_DRIVER=file');
});

it('session lifetime is 120 minutes', function () {
    expect(config('session.lifetime'))->toBe(120);
});

it('uses synchronous queue', function () {
    expect(config('queue.default'))->toBe('sync');
});

it('env file specifies log-based mail', function () {
    $envContent = file_get_contents(base_path('.env'));
    expect($envContent)->toContain('MAIL_MAILER=log');
});

it('has customer auth guard configured', function () {
    expect(config('auth.guards.customer'))->toBe([
        'driver' => 'session',
        'provider' => 'customers',
    ]);
});

it('has customers auth provider configured', function () {
    $provider = config('auth.providers.customers');
    expect($provider['driver'])->toBe('customer');
    expect($provider['model'])->toBe(App\Models\Customer::class);
});

it('has customers password broker configured', function () {
    $broker = config('auth.passwords.customers');
    expect($broker['provider'])->toBe('customers');
    expect($broker['table'])->toBe('customer_password_reset_tokens');
    expect($broker['expire'])->toBe(60);
    expect($broker['throttle'])->toBe(60);
});

it('has structured JSON logging channel configured', function () {
    $channel = config('logging.channels.structured');
    expect($channel)->not->toBeNull();
    expect($channel['driver'])->toBe('single');
    expect($channel['formatter'])->toBe(Monolog\Formatter\JsonFormatter::class);
});

it('has local filesystem as default disk', function () {
    expect(config('filesystems.default'))->toBe('local');
});

it('has public disk configured for local storage', function () {
    $public = config('filesystems.disks.public');
    expect($public['driver'])->toBe('local');
});
