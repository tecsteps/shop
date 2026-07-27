<?php

use App\Enums\MediaStatus;
use App\Jobs\ProcessMediaUpload;
use App\Models\Product;
use App\Models\ProductMedia;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');

    $this->store = $this->createStore();
    $this->product = Product::factory()->create(['store_id' => $this->store->id]);
});

/**
 * Write a real JPEG image of the given size to the fake public disk.
 */
function createTestImage(string $storageKey, int $width, int $height): void
{
    $image = imagecreatetruecolor($width, $height);
    imagefill($image, 0, 0, imagecolorallocate($image, 120, 80, 200));

    $path = Storage::disk('public')->path($storageKey);

    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0755, true);
    }

    imagejpeg($image, $path, 90);
    imagedestroy($image);
}

test('processing generates all four sizes with contained dimensions', function () {
    createTestImage('media/originals/photo.jpg', 800, 600);

    $media = ProductMedia::factory()->create([
        'product_id' => $this->product->id,
        'storage_key' => 'media/originals/photo.jpg',
    ]);

    (new ProcessMediaUpload($media))->handle();

    $expected = [
        'thumbnail' => [150, 113],
        'small' => [300, 225],
        'medium' => [600, 450],
        'large' => [800, 600], // never upscaled
    ];

    foreach ($expected as $size => [$expectedWidth, $expectedHeight]) {
        $path = $media->pathFor($size);

        expect(Storage::disk('public')->exists($path))->toBeTrue("Missing {$size}");

        [$width, $height] = getimagesize(Storage::disk('public')->path($path));

        expect($width)->toBe($expectedWidth, "Wrong width for {$size}")
            ->and($height)->toBe($expectedHeight, "Wrong height for {$size}");
    }
});

test('processing updates the record to ready with original metadata', function () {
    createTestImage('media/originals/meta.jpg', 1024, 768);

    $media = ProductMedia::factory()->create([
        'product_id' => $this->product->id,
        'storage_key' => 'media/originals/meta.jpg',
    ]);

    (new ProcessMediaUpload($media))->handle();

    $media->refresh();

    expect($media->status)->toBe(MediaStatus::Ready)
        ->and($media->width)->toBe(1024)
        ->and($media->height)->toBe(768)
        ->and($media->mime_type)->toBe('image/jpeg')
        ->and($media->byte_size)->toBe(Storage::disk('public')->size('media/originals/meta.jpg'));
});

test('aspect ratio is preserved for portrait images', function () {
    createTestImage('media/originals/portrait.jpg', 600, 1200);

    $media = ProductMedia::factory()->create([
        'product_id' => $this->product->id,
        'storage_key' => 'media/originals/portrait.jpg',
    ]);

    (new ProcessMediaUpload($media))->handle();

    [$width, $height] = getimagesize(Storage::disk('public')->path($media->pathFor('medium')));

    expect($width)->toBe(300)
        ->and($height)->toBe(600)
        ->and($width / $height)->toEqualWithDelta(600 / 1200, 0.01);
});

test('garbage bytes mark the media as failed', function () {
    Storage::disk('public')->put('media/originals/broken.jpg', 'this is not an image');

    $media = ProductMedia::factory()->create([
        'product_id' => $this->product->id,
        'storage_key' => 'media/originals/broken.jpg',
    ]);

    $job = new ProcessMediaUpload($media);

    try {
        $job->handle();
        $this->fail('Expected a RuntimeException for corrupt image data.');
    } catch (RuntimeException $exception) {
        $job->failed($exception);
    }

    expect($media->refresh()->status)->toBe(MediaStatus::Failed)
        // The original is kept for inspection.
        ->and(Storage::disk('public')->exists('media/originals/broken.jpg'))->toBeTrue();
});

test('deleting the media record removes all files from storage', function () {
    createTestImage('media/originals/cleanup.jpg', 400, 400);

    $media = ProductMedia::factory()->create([
        'product_id' => $this->product->id,
        'storage_key' => 'media/originals/cleanup.jpg',
    ]);

    (new ProcessMediaUpload($media))->handle();

    expect(Storage::disk('public')->exists($media->pathFor('thumbnail')))->toBeTrue();

    $media->delete();

    expect(Storage::disk('public')->exists('media/originals/cleanup.jpg'))->toBeFalse()
        ->and(Storage::disk('public')->exists($media->pathFor('thumbnail')))->toBeFalse()
        ->and(Storage::disk('public')->exists($media->pathFor('small')))->toBeFalse()
        ->and(Storage::disk('public')->exists($media->pathFor('medium')))->toBeFalse()
        ->and(Storage::disk('public')->exists($media->pathFor('large')))->toBeFalse();
});

test('media urls point at the public disk paths', function () {
    $media = ProductMedia::factory()->create([
        'product_id' => $this->product->id,
        'storage_key' => 'media/originals/u.jpg',
    ]);

    expect($media->url())->toContain('media/originals/u.jpg')
        ->and($media->urlFor('medium'))->toContain("media/{$this->product->id}/{$media->id}/medium.jpg");
});
