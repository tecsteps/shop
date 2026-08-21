<?php

use App\Models\Customer;
use App\Models\Store;
use App\Models\Theme;
use App\Models\ThemeSetting;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use App\Services\WebhookService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

test('theme settings use a one-to-one JSON contract and preserve model relations', function (): void {
    $columns = collect(Schema::getColumns('theme_settings'))->keyBy('name');
    $foreignKeys = collect(DB::select("PRAGMA foreign_key_list('theme_settings')"));

    expect($columns->keys()->all())->toBe(['theme_id', 'settings_json', 'updated_at'])
        ->and($columns['theme_id']['nullable'])->toBeFalse()
        ->and($columns['settings_json']['default'])->toBe("'{}'")
        ->and($foreignKeys->pluck('table')->all())->toContain('themes');

    $theme = Theme::factory()->create();
    $setting = $theme->themeSettings()->create([
        'settings_json' => ['primary_color' => '#1a1a2e', 'dark_mode' => true],
    ]);

    expect($setting)->toBeInstanceOf(ThemeSetting::class)
        ->and($setting->getKey())->toBe($theme->getKey())
        ->and($theme->fresh()->settingsRows->settings_json)->toBe([
            'primary_color' => '#1a1a2e',
            'dark_mode' => true,
        ]);
});

test('customer password reset tokens are non-null foreign-keyed and store scoped', function (): void {
    $columns = collect(Schema::getColumns('customer_password_reset_tokens'))->keyBy('name');
    $foreignKeys = collect(DB::select("PRAGMA foreign_key_list('customer_password_reset_tokens')"));

    expect($columns['store_id']['nullable'])->toBeFalse()
        ->and($foreignKeys->pluck('table')->all())->toContain('stores');

    $firstStore = Store::factory()->create();
    $secondStore = Store::factory()->create();
    $firstCustomer = Customer::factory()->create(['store_id' => $firstStore->getKey(), 'email' => 'same@example.test']);
    $secondCustomer = Customer::factory()->create(['store_id' => $secondStore->getKey(), 'email' => 'same@example.test']);

    app()->instance('current_store', $firstStore);
    $firstToken = Password::broker('customers')->createToken($firstCustomer);

    expect(DB::table('customer_password_reset_tokens')->where('store_id', $firstStore->getKey())->where('email', $firstCustomer->email)->exists())->toBeTrue();

    app()->instance('current_store', $secondStore);

    expect(Password::broker('customers')->tokenExists($secondCustomer, $firstToken))->toBeFalse();

    $secondToken = Password::broker('customers')->createToken($secondCustomer);

    expect(Password::broker('customers')->tokenExists($secondCustomer, $secondToken))->toBeTrue();
});

test('security defaults enable encrypted sessions, CORS credentials, and ninety-day audit retention', function (): void {
    $sessionEncryptEnvironment = $_ENV['SESSION_ENCRYPT'] ?? null;
    $sessionEncryptServer = $_SERVER['SESSION_ENCRYPT'] ?? null;
    $hasEnvironmentValue = array_key_exists('SESSION_ENCRYPT', $_ENV);
    $hasServerValue = array_key_exists('SESSION_ENCRYPT', $_SERVER);

    unset($_ENV['SESSION_ENCRYPT'], $_SERVER['SESSION_ENCRYPT']);
    putenv('SESSION_ENCRYPT');
    $sessionConfig = require config_path('session.php');

    if ($hasEnvironmentValue) {
        $_ENV['SESSION_ENCRYPT'] = $sessionEncryptEnvironment;
    }

    if ($hasServerValue) {
        $_SERVER['SESSION_ENCRYPT'] = $sessionEncryptServer;
    }

    expect($sessionConfig['encrypt'])->toBeTrue()
        ->and(config('cors.supports_credentials'))->toBeTrue()
        ->and(config('logging.channels.audit.days'))->toBe(90);
});

test('webhook secrets are encrypted under the contract name and sign the exact JSON request', function (): void {
    Http::fake(['https://hooks.test/*' => Http::response(['ok' => true], 200)]);
    $store = Store::factory()->create();
    app()->instance('current_store', $store);

    $subscription = WebhookSubscription::create([
        'event' => 'order.created',
        'target_url' => 'https://hooks.test/orders',
        'signing_secret_encrypted' => 'test-secret',
        'status' => 'active',
    ]);

    expect($subscription->signing_secret_encrypted)->toBe('test-secret')
        ->and($subscription->getHidden())->toContain('signing_secret_encrypted')
        ->and(DB::table('webhook_subscriptions')->where('id', $subscription->getKey())->value('signing_secret_encrypted'))->not->toBe('test-secret');

    (new WebhookService)->dispatch($store, 'order.created', ['order_id' => 1001]);

    Http::assertSent(function ($request): bool {
        $body = $request->body();

        return $request->header('Content-Type')[0] === 'application/json'
            && $request->header('X-Platform-Event')[0] === 'order.created'
            && $request->header('X-Platform-Signature')[0] === hash_hmac('sha256', $body, 'test-secret');
    });

    expect(WebhookDelivery::query()->where('webhook_subscription_id', $subscription->getKey())->firstOrFail()->status)->toBe('delivered');
});
