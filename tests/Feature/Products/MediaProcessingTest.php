<?php

use App\Enums\MediaStatus;
use App\Jobs\ProcessMediaUpload;
use App\Models\Product;
use App\Models\ProductMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\Jobs\FakeJob;
use Illuminate\Support\Facades\Storage;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('media processing stores resized image variants and webp copies', function () {
    Storage::fake('public');

    $media = createProcessedProductMediaForMediaProcessingTest();

    expect($media->status)->toBe(MediaStatus::Ready)
        ->and($media->width)->toBe(1600)
        ->and($media->height)->toBe(1000)
        ->and($media->mime_type)->toBe('image/jpeg')
        ->and($media->byte_size)->toBeGreaterThan(0);

    foreach (ProductMedia::ImageVariantSizes as $size => $bounds) {
        foreach (['jpg', 'webp'] as $extension) {
            $key = $media->imageVariantStorageKey($size, $extension);

            Storage::disk('public')->assertExists($key);

            [$width, $height] = getimagesize(Storage::disk('public')->path($key));

            expect($width)->toBeLessThanOrEqual($bounds['width'])
                ->and($height)->toBeLessThanOrEqual($bounds['height']);
        }
    }
});

test('deleting product media removes original and generated variants', function () {
    Storage::fake('public');

    $media = createProcessedProductMediaForMediaProcessingTest();
    $paths = [
        $media->storage_key,
        $media->imageVariantStorageKey('thumbnail', 'jpg'),
        $media->imageVariantStorageKey('thumbnail', 'webp'),
        $media->imageVariantStorageKey('large', 'jpg'),
        $media->imageVariantStorageKey('large', 'webp'),
    ];

    foreach ($paths as $path) {
        Storage::disk('public')->assertExists($path);
    }

    $media->delete();

    foreach ($paths as $path) {
        Storage::disk('public')->assertMissing($path);
    }
});

test('media processing marks missing originals as failed', function () {
    Storage::fake('public');

    $media = ProductMedia::factory()->processing()->create([
        'storage_key' => 'media/originals/missing.jpg',
    ]);

    (new ProcessMediaUpload($media))->handle();

    expect($media->refresh()->status)->toBe(MediaStatus::Failed);
});

test('media processing retries invalid images before final failure', function () {
    Storage::fake('public');
    Storage::disk('public')->put('media/originals/not-an-image.jpg', 'not an image');

    $media = ProductMedia::factory()->processing()->create([
        'storage_key' => 'media/originals/not-an-image.jpg',
    ]);

    expect(fn () => (new ProcessMediaUpload($media))->handle())
        ->toThrow(\RuntimeException::class)
        ->and($media->refresh()->status)->toBe(MediaStatus::Processing);

    $fakeJob = new FakeJob;
    $fakeJob->attempts = 3;
    $finalAttempt = new ProcessMediaUpload($media->refresh());

    $finalAttempt->setJob($fakeJob)->handle();

    expect($media->refresh()->status)->toBe(MediaStatus::Failed);
});

function createProcessedProductMediaForMediaProcessingTest(): ProductMedia
{
    $product = Product::factory()->create();
    $file = UploadedFile::fake()->image('linen-shirt.jpg', 1600, 1000)->size(120);
    $storageKey = 'media/originals/'.$file->hashName();

    Storage::disk('public')->put($storageKey, (string) file_get_contents($file->getRealPath()));

    $media = ProductMedia::factory()->for($product)->processing()->create([
        'storage_key' => $storageKey,
        'width' => null,
        'height' => null,
        'mime_type' => null,
        'byte_size' => null,
    ]);

    (new ProcessMediaUpload($media))->handle();

    return $media->refresh();
}
