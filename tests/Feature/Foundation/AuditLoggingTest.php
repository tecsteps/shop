<?php

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\AuditLogger;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Auth\Events\Failed as AuthFailed;
use Illuminate\Auth\Events\Login as AuthLogin;
use Illuminate\Auth\Events\Logout as AuthLogout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();

    auditLoggingConfigureChannel();
    auditLoggingResetFiles();

    $this->seed(DatabaseSeeder::class);

    Log::forgetChannel('audit');
    auditLoggingResetFiles();
});

function auditLoggingConfigureChannel(): void
{
    config(['logging.channels.audit.path' => storage_path('framework/testing/audit.log')]);
    Log::forgetChannel('audit');
}

function auditLoggingResetFiles(): void
{
    $directory = storage_path('framework/testing');

    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    foreach (glob($directory.'/audit*.log') ?: [] as $file) {
        unlink($file);
    }
}

function auditLoggingPath(): string
{
    return storage_path('framework/testing/audit-'.now()->format('Y-m-d').'.log');
}

function auditLoggingContents(): string
{
    $path = auditLoggingPath();

    return file_exists($path) ? (string) file_get_contents($path) : '';
}

test('audit logger writes structured entries to the audit channel', function (): void {
    app(AuditLogger::class)->log(
        event: 'test.event',
        userId: 1,
        storeId: 2,
        resourceType: 'product',
        resourceId: 3,
        changes: ['title' => ['Old title', 'New title']],
        extra: ['source' => 'test-suite'],
    );

    expect(auditLoggingContents())->toContain('"event":"test.event"')
        ->toContain('"user_id":1')
        ->toContain('"store_id":2')
        ->toContain('"resource_type":"product"')
        ->toContain('"resource_id":3')
        ->toContain('"title":["Old title","New title"]')
        ->toContain('"source":"test-suite"');
});

test('authentication and resource changes are audit logged', function (): void {
    $user = User::query()->where('email', 'admin@acme.test')->firstOrFail();
    $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $product = Product::query()->where('store_id', $store->getKey())->firstOrFail();

    app()->instance('current_store', $store);
    $this->actingAs($user);

    event(new AuthLogin('web', $user, false));
    event(new AuthFailed('web', null, ['email' => 'failed-admin@example.test']));
    event(new AuthLogout('web', $user));

    $product->forceFill([
        'title' => 'Audit Trail Jacket',
    ])->save();

    expect(auditLoggingContents())->toContain('"event":"auth.login"')
        ->toContain('"event":"auth.failed_login"')
        ->toContain('"email":"failed-admin@example.test"')
        ->toContain('"event":"auth.logout"')
        ->toContain('"event":"product.updated"')
        ->toContain('"resource_type":"product"')
        ->toContain('"title"')
        ->toContain('Audit Trail Jacket');
});
