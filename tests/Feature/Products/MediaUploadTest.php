<?php

use App\Enums\MediaStatus;
use App\Jobs\ProcessMediaUpload;
use App\Models\Product;
use App\Services\MediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Storage::fake('public');
});

it('uploads an image for a product', function () {
    Queue::fake();

    $context = createStoreContext();
    $product = Product::factory()->for($context['store'])->create();

    $media = app(MediaService::class)->attach($product, UploadedFile::fake()->image('photo.jpg', 800, 600));

    $this->assertDatabaseHas('product_media', [
        'id' => $media->getKey(),
        'product_id' => $product->getKey(),
        'type' => 'image',
        'status' => 'processing',
    ]);

    Storage::disk('public')->assertExists($media->storage_key);
    Queue::assertPushed(ProcessMediaUpload::class);
});

it('processes uploaded image and generates variants', function () {
    $context = createStoreContext();
    $product = Product::factory()->for($context['store'])->create();

    Queue::fake();
    $media = app(MediaService::class)->attach($product, UploadedFile::fake()->image('photo.jpg', 1600, 900));

    (new ProcessMediaUpload($media))->handle();

    $media->refresh();

    expect($media->status)->toBe(MediaStatus::Ready);
    expect($media->width)->toBe(1600);
    expect($media->height)->toBe(900);

    Storage::disk('public')->assertExists($media->storage_key);
    Storage::disk('public')->assertExists($media->derivedStorageKey('thumbnail'));
    Storage::disk('public')->assertExists($media->derivedStorageKey('medium'));
    Storage::disk('public')->assertExists($media->derivedStorageKey('large'));
});

it('rejects non-image file types', function () {
    $context = createStoreContext();
    $product = Product::factory()->for($context['store'])->create();

    $file = UploadedFile::fake()->create('notes.txt', 5, 'text/plain');

    expect(fn () => app(MediaService::class)->attach($product, $file))
        ->toThrow(ValidationException::class);

    $this->assertDatabaseCount('product_media', 0);
});

it('sets alt text on media', function () {
    Queue::fake();

    $context = createStoreContext();
    $product = Product::factory()->for($context['store'])->create();

    $media = app(MediaService::class)->attach($product, UploadedFile::fake()->image('photo.jpg'));

    app(MediaService::class)->updateAltText($media, 'A red t-shirt on a hanger');

    $this->assertDatabaseHas('product_media', [
        'id' => $media->getKey(),
        'alt_text' => 'A red t-shirt on a hanger',
    ]);
});

it('reorders media positions', function () {
    Queue::fake();

    $context = createStoreContext();
    $product = Product::factory()->for($context['store'])->create();
    $service = app(MediaService::class);

    $first = $service->attach($product, UploadedFile::fake()->image('one.jpg'));
    $second = $service->attach($product, UploadedFile::fake()->image('two.jpg'));
    $third = $service->attach($product, UploadedFile::fake()->image('three.jpg'));

    expect([$first->position, $second->position, $third->position])->toBe([0, 1, 2]);

    $service->reorder($product, [$third->getKey(), $first->getKey(), $second->getKey()]);

    expect($third->refresh()->position)->toBe(0);
    expect($first->refresh()->position)->toBe(1);
    expect($second->refresh()->position)->toBe(2);
});

it('deletes media and removes file from storage', function () {
    $context = createStoreContext();
    $product = Product::factory()->for($context['store'])->create();
    $service = app(MediaService::class);

    Queue::fake();
    $media = $service->attach($product, UploadedFile::fake()->image('photo.jpg', 400, 400));
    (new ProcessMediaUpload($media))->handle();

    $storageKey = $media->storage_key;
    $thumbnailKey = $media->derivedStorageKey('thumbnail');

    $service->delete($media);

    $this->assertDatabaseMissing('product_media', ['id' => $media->getKey()]);
    Storage::disk('public')->assertMissing($storageKey);
    Storage::disk('public')->assertMissing($thumbnailKey);
});
