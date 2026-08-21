<?php

use App\Jobs\ProcessMediaUpload;
use App\Livewire\Admin\Developers\Index as DeveloperSettings;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\Store;
use App\Models\StoreInvitation;
use App\Models\User;
use App\Models\WebhookSubscription;
use Database\Seeders\ShopSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    config(['cache.default' => 'array']);
    $this->seed(ShopSeeder::class);
    $this->store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $this->admin = User::query()->where('email', 'admin@acme.test')->firstOrFail();
    app()->instance('current_store', $this->store);
});

test('platform API creates organizations, stores, invitations, and membership responses', function (): void {
    $token = $this->admin->createToken('platform-manager', ['manage-platform'])->plainTextToken;

    $organization = $this->withToken($token)->postJson('http://shop.test/api/admin/v1/platform/organizations', [
        'name' => 'Northwind',
        'billing_email' => 'billing@northwind.test',
    ])->assertCreated()->json('data');

    $createdStore = $this->withToken($token)->postJson('http://shop.test/api/admin/v1/platform/stores', [
        'organization_id' => $organization['id'],
        'name' => 'Northwind Shop',
        'handle' => 'northwind-shop',
        'default_currency' => 'EUR',
        'default_locale' => 'en',
        'timezone' => 'Europe/Berlin',
    ])->assertCreated()->json('data');

    $this->withToken($token)->postJson("http://shop.test/api/admin/v1/stores/{$this->store->getKey()}/invites", [
        'email' => 'new-staff@example.test',
        'role' => 'staff',
    ])->assertCreated()->assertJsonPath('data.role', 'staff');

    $this->withToken($this->admin->createToken('membership-reader', ['read-products'])->plainTextToken)
        ->getJson("http://shop.test/api/admin/v1/stores/{$this->store->getKey()}/me")
        ->assertOk()
        ->assertJsonPath('data.store_id', $this->store->getKey())
        ->assertJsonPath('data.role', 'owner');

    expect(Organization::query()->whereKey($organization['id'])->exists())->toBeTrue()
        ->and(Store::query()->whereKey($createdStore['id'])->value('handle'))->toBe('northwind-shop')
        ->and(StoreInvitation::query()->where('email', 'new-staff@example.test')->exists())->toBeTrue();
});

test('product API persists nested options variants inventory and collections', function (): void {
    $token = $this->admin->createToken('catalog-writer', ['write-products'])->plainTextToken;
    $collection = \App\Models\Collection::withoutGlobalScopes()->create(['store_id' => $this->store->getKey(), 'title' => 'Nested Collection', 'handle' => 'nested-collection', 'type' => 'manual', 'status' => 'active']);

    $response = $this->withToken($token)->postJson("http://shop.test/api/admin/v1/stores/{$this->store->getKey()}/products", [
        'title' => 'Nested Catalog Product',
        'status' => 'draft',
        'options' => [['name' => 'Color', 'position' => 1]],
        'variants' => [[
            'sku' => 'NESTED-BLUE',
            'price_amount' => 2500,
            'currency' => 'EUR',
            'requires_shipping' => true,
            'is_default' => true,
            'option_values' => [['option_name' => 'Color', 'value' => 'Blue']],
            'inventory' => ['quantity_on_hand' => 50, 'policy' => 'deny'],
        ]],
        'collections' => [$collection->getKey()],
    ])->assertCreated();

    $product = Product::withoutGlobalScopes()->where('title', 'Nested Catalog Product')->with(['options.values', 'variants.inventory', 'variants.optionValues', 'collections'])->firstOrFail();

    expect($response->json('data.options.0.name'))->toBe('Color')
        ->and($product->variants->first()->inventory->quantity_on_hand)->toBe(50)
        ->and($product->variants->first()->optionValues->first()->value)->toBe('Blue')
        ->and($product->collections->contains($collection))->toBeTrue();

    $presigned = $this->withToken($token)->postJson("http://shop.test/api/admin/v1/stores/{$this->store->getKey()}/products/{$product->getKey()}/media/presign-upload", [
        'filename' => 'product-image.jpg',
        'content_type' => 'image/jpeg',
        'byte_size' => 1200,
    ])->assertCreated()->json();

    expect($presigned['method'])->toBe('PUT')->and($presigned['media_id'])->toBeInt();
});

test('media processing records metadata, dimensions, derivatives, and failure state', function (): void {
    Storage::fake('public');
    $product = Product::query()->firstOrFail();
    $key = 'stores/'.$this->store->getKey().'/products/'.$product->getKey().'/media/source.jpg';
    $image = imagecreatetruecolor(640, 400);
    ob_start();
    imagejpeg($image, null, 90);
    $contents = ob_get_clean();
    imagedestroy($image);
    Storage::disk('public')->put($key, $contents);

    $media = ProductMedia::create(['product_id' => $product->getKey(), 'type' => 'image', 'path' => $key, 'storage_key' => $key, 'mime_type' => 'image/jpeg', 'status' => 'processing']);
    (new ProcessMediaUpload($media))->handle();

    expect($media->refresh()->status)->toBe('ready')
        ->and($media->width)->toBe(640)
        ->and($media->height)->toBe(400)
        ->and($media->metadata['variants'])->toHaveKeys(['original', 'thumbnail', 'medium', 'large'])
        ->and($media->checksum)->toBe(hash('sha256', $contents));

    $failed = ProductMedia::create(['product_id' => $product->getKey(), 'type' => 'image', 'path' => 'missing.jpg', 'status' => 'processing']);
    $exception = new RuntimeException('missing upload');
    (new ProcessMediaUpload($failed))->failed($exception);

    expect($failed->refresh()->status)->toBe('failed')
        ->and($failed->metadata['error'])->toBe('missing upload');
});

test('developer webhook creation writes the normalized event type', function (): void {
    $this->actingAs($this->admin);

    Livewire::test(DeveloperSettings::class)
        ->set('event', 'order.created')
        ->set('targetUrl', 'https://hooks.example.test/orders')
        ->call('createWebhook')
        ->assertHasNoErrors();

    expect(WebhookSubscription::query()->where('target_url', 'https://hooks.example.test/orders')->value('event_type'))->toBe('order.created');
});

test('deferred OAuth endpoints return not implemented responses', function (): void {
    $this->postJson('http://shop.test/oauth/token')->assertStatus(501);
});
