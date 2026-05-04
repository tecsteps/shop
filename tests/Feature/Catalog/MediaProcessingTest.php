<?php

use App\Enums\MediaStatus;
use App\Jobs\ProcessMediaUpload;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('media processing generates resized files and cleans them up on delete', function () {
    Storage::fake('public');

    $store = Store::factory()->create();
    $product = Product::factory()->create(['store_id' => $store->getKey()]);
    $media = ProductMedia::factory()->processing()->create([
        'product_id' => $product->getKey(),
        'storage_key' => 'media/originals/test.png',
        'width' => null,
        'height' => null,
        'mime_type' => null,
        'byte_size' => null,
    ]);

    Storage::disk('public')->put($media->storage_key, catalogPngImageContents());

    (new ProcessMediaUpload($media->getKey(), $store->getKey()))->handle();

    $media->refresh();

    expect($media->status)->toBe(MediaStatus::Ready)
        ->and($media->width)->toBe(20)
        ->and($media->height)->toBe(10)
        ->and($media->mime_type)->toBe('image/png')
        ->and($media->byte_size)->toBeGreaterThan(0);

    foreach (['thumbnail', 'small', 'medium', 'large'] as $size) {
        Storage::disk('public')->assertExists("media/{$product->getKey()}/{$media->getKey()}/{$size}.png");

        if (function_exists('imagewebp')) {
            Storage::disk('public')->assertExists("media/{$product->getKey()}/{$media->getKey()}/{$size}.webp");
        }
    }

    $media->delete();

    Storage::disk('public')->assertMissing('media/originals/test.png');
    Storage::disk('public')->assertMissing("media/{$product->getKey()}/{$media->getKey()}/thumbnail.png");
});

test('media processing marks invalid image uploads as failed', function () {
    Storage::fake('public');

    $store = Store::factory()->create();
    $product = Product::factory()->create(['store_id' => $store->getKey()]);
    $media = ProductMedia::factory()->processing()->create([
        'product_id' => $product->getKey(),
        'storage_key' => 'media/originals/not-image.txt',
    ]);

    Storage::disk('public')->put($media->storage_key, 'not an image');

    expect(fn () => (new ProcessMediaUpload($media->getKey(), $store->getKey()))->handle())
        ->toThrow(RuntimeException::class);

    expect($media->refresh()->status)->toBe(MediaStatus::Failed);
});

function catalogPngImageContents(): string
{
    $image = imagecreatetruecolor(20, 10);
    imagefill($image, 0, 0, imagecolorallocate($image, 20, 80, 140));

    ob_start();
    imagepng($image);
    $contents = ob_get_clean();

    imagedestroy($image);

    if ($contents === false) {
        throw new RuntimeException('Unable to create test image.');
    }

    return $contents;
}
