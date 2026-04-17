<?php

namespace App\Jobs;

use App\Enums\MediaStatus;
use App\Models\ProductMedia;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

class ProcessMediaUpload implements ShouldQueue
{
    use Queueable;

    /** @var array<string, array{width: int, height: int}> */
    private const SIZES = [
        'thumbnail' => ['width' => 150, 'height' => 150],
        'medium' => ['width' => 600, 'height' => 600],
        'large' => ['width' => 1200, 'height' => 1200],
    ];

    public function __construct(
        public readonly int $mediaId,
    ) {}

    public function handle(): void
    {
        /** @var ProductMedia|null $media */
        $media = ProductMedia::query()->find($this->mediaId);

        if (! $media || $media->status !== MediaStatus::Processing) {
            return;
        }

        try {
            $disk = Storage::disk('public');

            if (! $disk->exists($media->storage_key)) {
                $media->update(['status' => MediaStatus::Failed]);

                return;
            }

            if (class_exists(ImageManager::class)) {
                $originalPath = $disk->path($media->storage_key);
                $directory = pathinfo($media->storage_key, PATHINFO_DIRNAME);
                $filename = pathinfo($media->storage_key, PATHINFO_FILENAME);
                $extension = pathinfo($media->storage_key, PATHINFO_EXTENSION);

                $manager = ImageManager::gd();

                foreach (self::SIZES as $sizeName => $dimensions) {
                    $image = $manager->read($originalPath);
                    $image->cover($dimensions['width'], $dimensions['height']);

                    $resizedKey = "{$directory}/{$filename}_{$sizeName}.{$extension}";
                    $disk->put($resizedKey, (string) $image->toJpeg());
                }

                $original = $manager->read($originalPath);
                $media->update([
                    'width' => $original->width(),
                    'height' => $original->height(),
                    'byte_size' => $disk->size($media->storage_key),
                    'status' => MediaStatus::Ready,
                ]);
            } else {
                $media->update([
                    'byte_size' => $disk->size($media->storage_key),
                    'status' => MediaStatus::Ready,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Media processing failed', [
                'media_id' => $this->mediaId,
                'error' => $e->getMessage(),
            ]);

            $media->update(['status' => MediaStatus::Failed]);
        }
    }
}
