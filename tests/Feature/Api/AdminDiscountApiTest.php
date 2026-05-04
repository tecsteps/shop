<?php

use App\Models\Collection;
use App\Models\Discount;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\WebhookService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function adminDiscountApiStore(): Store
{
    return Store::query()->where('handle', 'acme-fashion')->firstOrFail();
}

function adminDiscountApiUser(): User
{
    return User::query()->where('email', 'admin@acme.test')->firstOrFail();
}

/**
 * @param  list<string>  $abilities
 * @return array{token: \App\Models\OauthToken, plain_text: string}
 */
function adminDiscountApiToken(Store $store, array $abilities): array
{
    return app(WebhookService::class)->createApiToken($store, 'Discount integration', $abilities);
}

test('admin discount api lists creates updates and deletes discounts', function (): void {
    $store = adminDiscountApiStore();
    $product = Product::factory()->withDefaultVariant()->create(['store_id' => $store->getKey()]);
    $collection = Collection::factory()->create(['store_id' => $store->getKey()]);
    $user = adminDiscountApiUser();

    $this->actingAs($user)
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/discounts?type=code&status=active")
        ->assertOk();

    $createResponse = $this->actingAs($user)
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/discounts", [
            'type' => 'code',
            'code' => 'api20',
            'value_type' => 'percent',
            'value_amount' => 20,
            'starts_at' => now()->subHour()->toIso8601String(),
            'ends_at' => now()->addMonth()->toIso8601String(),
            'usage_limit' => 50,
            'rules_json' => [
                'minimum_purchase_amount' => 5000,
                'applicable_product_ids' => [$product->getKey()],
                'applicable_collection_ids' => [$collection->getKey()],
                'customer_eligibility' => 'all',
                'once_per_customer' => true,
            ],
        ])
        ->assertCreated()
        ->assertJsonPath('data.code', 'API20')
        ->assertJsonPath('data.rules_json.min_purchase_amount', 5000)
        ->assertJsonPath('data.rules_json.one_per_customer', true);

    $discount = Discount::withoutGlobalScopes()->findOrFail($createResponse->json('data.id'));

    expect($discount->rules_json['applicable_product_ids'])->toBe([$product->getKey()])
        ->and($discount->rules_json['applicable_collection_ids'])->toBe([$collection->getKey()]);

    $this->actingAs($user)
        ->putJson("/api/admin/v1/stores/{$store->getKey()}/discounts/{$discount->getKey()}", [
            'value_type' => 'fixed',
            'value_amount' => 750,
            'usage_limit' => 25,
            'status' => 'disabled',
        ])
        ->assertOk()
        ->assertJsonPath('data.value_type', 'fixed')
        ->assertJsonPath('data.value_amount', 750)
        ->assertJsonPath('data.status', 'disabled');

    $this->actingAs($user)
        ->deleteJson("/api/admin/v1/stores/{$store->getKey()}/discounts/{$discount->getKey()}")
        ->assertOk()
        ->assertJsonPath('message', 'Discount deleted');

    expect(Discount::withoutGlobalScopes()->whereKey($discount->getKey())->exists())->toBeFalse();
});

test('admin discount api enforces token abilities and store scope', function (): void {
    $store = adminDiscountApiStore();
    $otherStore = Store::factory()->create();
    $discount = Discount::factory()->create([
        'store_id' => $store->getKey(),
        'code' => 'TOKEN25',
    ]);
    $readToken = adminDiscountApiToken($store, ['read-discounts']);
    $writeToken = adminDiscountApiToken($store, ['write-discounts']);
    $otherStoreToken = adminDiscountApiToken($otherStore, ['read-discounts']);

    $this->withToken($readToken['plain_text'])
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/discounts?type=code")
        ->assertOk()
        ->assertJsonFragment(['code' => 'TOKEN25']);

    $this->withToken($readToken['plain_text'])
        ->deleteJson("/api/admin/v1/stores/{$store->getKey()}/discounts/{$discount->getKey()}")
        ->assertForbidden();

    $this->withToken($otherStoreToken['plain_text'])
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/discounts")
        ->assertForbidden();

    $this->withToken($writeToken['plain_text'])
        ->deleteJson("/api/admin/v1/stores/{$store->getKey()}/discounts/{$discount->getKey()}")
        ->assertOk();
});

test('admin discount api validates code dates and scoped rule resources', function (): void {
    $store = adminDiscountApiStore();
    $existing = Discount::factory()->create([
        'store_id' => $store->getKey(),
        'code' => 'EXISTING',
    ]);
    $otherStoreProduct = Product::factory()
        ->withDefaultVariant()
        ->create(['store_id' => Store::factory()->create()->getKey()]);

    $this->actingAs(adminDiscountApiUser())
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/discounts", [
            'type' => 'code',
            'code' => Str::lower($existing->code),
            'value_type' => 'percent',
            'value_amount' => 101,
            'starts_at' => now()->addDay()->toIso8601String(),
            'ends_at' => now()->subDay()->toIso8601String(),
            'rules_json' => [
                'applicable_product_ids' => [$otherStoreProduct->getKey()],
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['code', 'value_amount', 'ends_at', 'rules_json.applicable_product_ids.0']);
});
