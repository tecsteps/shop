<?php

use App\Enums\MediaStatus;
use App\Enums\StoreUserRole;
use App\Jobs\ProcessMediaUpload;
use App\Livewire\Admin\Products\Form as ProductForm;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function productMediaAdminUser(Store $store): User
{
    $user = User::factory()->create();
    $user->stores()->attach($store->getKey(), [
        'role' => StoreUserRole::Admin->value,
    ]);

    return $user;
}

test('admin product form uploads images and queues media processing', function (): void {
    Storage::fake('public');
    Queue::fake([ProcessMediaUpload::class]);

    $store = Store::factory()->create();
    $user = productMediaAdminUser($store);
    $product = Product::factory()->create([
        'store_id' => $store->getKey(),
        'title' => 'Media Test Product',
    ]);

    app()->instance('current_store', $store);

    Livewire::actingAs($user)
        ->test(ProductForm::class, ['product' => $product])
        ->set('newMedia', [
            UploadedFile::fake()->image('front.jpg', 40, 30),
        ])
        ->call('uploadMedia')
        ->assertHasNoErrors()
        ->assertSee('Media uploaded');

    $media = ProductMedia::withoutGlobalScopes()
        ->where('product_id', $product->getKey())
        ->firstOrFail();

    expect($media->status)->toBe(MediaStatus::Processing)
        ->and($media->position)->toBe(0)
        ->and($media->alt_text)->toBe('Media Test Product')
        ->and($media->storage_key)->toStartWith("media/originals/{$product->getKey()}/");

    Storage::disk('public')->assertExists($media->storage_key);

    Queue::assertPushed(ProcessMediaUpload::class, function (ProcessMediaUpload $job) use ($media, $store): bool {
        return $job->productMediaId === $media->getKey()
            && $job->storeId === $store->getKey();
    });
});

test('admin product form attaches selected media when creating a product', function (): void {
    Storage::fake('public');
    Queue::fake([ProcessMediaUpload::class]);

    $store = Store::factory()->create();
    $user = productMediaAdminUser($store);

    app()->instance('current_store', $store);

    Livewire::actingAs($user)
        ->test(ProductForm::class)
        ->set('title', 'Created Media Product')
        ->set('handle', 'created-media-product')
        ->set('variants.0.price', '19.99')
        ->set('variants.0.quantity', 7)
        ->set('newMedia', [
            UploadedFile::fake()->image('created.png', 32, 24),
        ])
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Product saved');

    $product = Product::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('handle', 'created-media-product')
        ->firstOrFail();
    $media = ProductMedia::withoutGlobalScopes()
        ->where('product_id', $product->getKey())
        ->firstOrFail();

    Storage::disk('public')->assertExists($media->storage_key);
    Queue::assertPushed(ProcessMediaUpload::class, 1);
});

test('admin product form rejects non image media uploads', function (): void {
    Storage::fake('public');
    Queue::fake([ProcessMediaUpload::class]);

    $store = Store::factory()->create();
    $user = productMediaAdminUser($store);
    $product = Product::factory()->create(['store_id' => $store->getKey()]);

    app()->instance('current_store', $store);

    Livewire::actingAs($user)
        ->test(ProductForm::class, ['product' => $product])
        ->set('newMedia', [
            UploadedFile::fake()->create('notes.txt', 1, 'text/plain'),
        ])
        ->call('uploadMedia')
        ->assertHasErrors(['newMedia.0']);

    expect(ProductMedia::withoutGlobalScopes()->where('product_id', $product->getKey())->count())->toBe(0);

    Queue::assertNothingPushed();
});

test('admin product form updates alt text reorders and deletes media', function (): void {
    Storage::fake('public');

    $store = Store::factory()->create();
    $user = productMediaAdminUser($store);
    $product = Product::factory()->create(['store_id' => $store->getKey()]);

    $media = collect([0, 1, 2])->map(function (int $position) use ($product): ProductMedia {
        $item = ProductMedia::factory()->create([
            'product_id' => $product->getKey(),
            'storage_key' => "media/originals/{$product->getKey()}/{$position}.png",
            'position' => $position,
            'status' => MediaStatus::Ready,
        ]);

        Storage::disk('public')->put($item->storage_key, 'image-bytes');

        return $item;
    });

    app()->instance('current_store', $store);

    $component = Livewire::actingAs($user)
        ->test(ProductForm::class, ['product' => $product])
        ->set('media.1.altText', 'Updated sleeve detail')
        ->call('updateMediaAlt', $media[1]->getKey())
        ->assertHasNoErrors();

    expect($media[1]->refresh()->alt_text)->toBe('Updated sleeve detail');

    $component->call('moveMedia', $media[2]->getKey(), 'up');

    expect($media[0]->refresh()->position)->toBe(0)
        ->and($media[2]->refresh()->position)->toBe(1)
        ->and($media[1]->refresh()->position)->toBe(2);

    $component->call('removeMedia', $media[2]->getKey());

    $this->assertModelMissing($media[2]);
    Storage::disk('public')->assertMissing($media[2]->storage_key);
});
