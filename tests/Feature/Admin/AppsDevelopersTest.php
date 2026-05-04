<?php

use App\Enums\StoreUserRole;
use App\Enums\WebhookEventType;
use App\Livewire\Admin\Developers\Index as DevelopersIndex;
use App\Models\OauthToken;
use App\Models\Store;
use App\Models\User;
use App\Models\WebhookSubscription;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function appsDevelopersStore(): Store
{
    return Store::query()->where('handle', 'acme-fashion')->firstOrFail();
}

function appsDevelopersUser(?StoreUserRole $role = null): User
{
    $store = appsDevelopersStore();
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    DB::table('store_users')->insert([
        'store_id' => $store->getKey(),
        'user_id' => $user->getKey(),
        'role' => ($role ?? StoreUserRole::Owner)->value,
        'created_at' => now(),
    ]);

    return $user;
}

test('apps route renders installed apps for owners and admins', function (): void {
    $this->actingAs(appsDevelopersUser(StoreUserRole::Admin))
        ->get('/admin/apps')
        ->assertSuccessful()
        ->assertSee('Apps')
        ->assertSee('Inventory Sync');
});

test('developer route rejects staff users', function (): void {
    $this->actingAs(appsDevelopersUser(StoreUserRole::Staff))
        ->get('/admin/developers')
        ->assertForbidden();
});

test('developers component generates tokens and manages webhooks', function (): void {
    $store = appsDevelopersStore();
    app()->instance('current_store', $store);
    $user = appsDevelopersUser(StoreUserRole::Owner);

    $initialTokenCount = OauthToken::query()
        ->whereHas('installation', fn ($query) => $query->withoutGlobalScopes()->where('store_id', $store->getKey()))
        ->count();

    Livewire::actingAs($user)
        ->test(DevelopersIndex::class)
        ->assertSee('API tokens')
        ->set('newTokenName', 'CI Pipeline')
        ->call('generateToken')
        ->assertHasNoErrors()
        ->assertSet('generatedToken', fn (?string $token): bool => str_starts_with((string) $token, 'shop_'))
        ->call('openWebhookModal')
        ->set('webhookEventType', WebhookEventType::OrderCreated->value)
        ->set('webhookUrl', 'https://example.com/webhooks/orders')
        ->call('saveWebhook')
        ->assertHasNoErrors()
        ->assertSee('https://example.com/webhooks/orders');

    expect(OauthToken::query()
        ->whereHas('installation', fn ($query) => $query->withoutGlobalScopes()->where('store_id', $store->getKey()))
        ->count())->toBe($initialTokenCount + 1)
        ->and(WebhookSubscription::withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->where('target_url', 'https://example.com/webhooks/orders')
            ->exists())->toBeTrue();
});
