<?php

use App\Models\Product;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

it('writes structured audit entries for resource changes and excludes credentials', function () {
    $store = shopStore();
    app()->instance('current_store', $store);
    $basePath = storage_path('logs/audit-test-'.Str::uuid().'.log');
    $dailyPath = $basePath;
    config()->set('logging.channels.audit.driver', 'single');
    config()->set('logging.channels.audit.path', $basePath);
    Log::forgetChannel('audit');

    try {
        $product = Product::factory()->create(['store_id' => $store->id, 'title' => 'Before title']);
        $product->update(['title' => 'After title']);
        Event::dispatch(new Failed('web', App\Models\User::class, [
            'email' => 'failed@example.test',
            'password' => 'never-log-this',
        ]));
        app(AuditLogger::class)->log('audit.example', extra: ['password' => 'secret', 'details' => ['api_token' => 'secret-token']]);

        $entries = collect(file($dailyPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES))
            ->map(function (string $line): array {
                $entry = json_decode($line, true, flags: JSON_THROW_ON_ERROR);

                return is_array($entry['context'] ?? null) ? $entry['context'] : $entry;
            });
        $productUpdate = $entries->firstWhere('event', 'product.updated');
        $failedLogin = $entries->firstWhere('event', 'auth.failed_login');
        $example = $entries->firstWhere('event', 'audit.example');

        expect($productUpdate['store_id'])->toBe($store->id)
            ->and($productUpdate['resource_type'])->toBe('product')
            ->and($productUpdate['resource_id'])->toBe($product->id)
            ->and($productUpdate['changes']['title'])->toBe(['Before title', 'After title'])
            ->and($failedLogin['email'])->toBe('failed@example.test')
            ->and(json_encode($failedLogin))->not->toContain('never-log-this')
            ->and($example['password'])->toBe('[redacted]')
            ->and($example['details']['api_token'])->toBe('[redacted]')
            ->and($example['timestamp'])->toBeString()
            ->and($example)->toHaveKeys(['ip', 'user_agent']);
    } finally {
        if (is_file($dailyPath)) {
            unlink($dailyPath);
        }

        Log::forgetChannel('audit');
    }
});

it('configures a daily audit channel with ninety day retention', function () {
    expect(config('logging.channels.audit.driver'))->toBe('daily')
        ->and(config('logging.channels.audit.path'))->toBe(storage_path('logs/audit.log'))
        ->and(config('logging.channels.audit.level'))->toBe('info')
        ->and(config('logging.channels.audit.days'))->toBe(90);
});
