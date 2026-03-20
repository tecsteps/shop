<?php

namespace App\Jobs;

use App\Enums\MediaStatus;
use App\Models\ProductMedia;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ProcessMediaUpload implements ShouldQueue
{
    use Queueable;

    private const SIZES = [
        'thumbnail' => [150, 150],
        'medium' => [600, 600],
        'large' => [1200, 1200],
    ];

    public function __construct(
        public readonly ProductMedia $media,
    ) {}

    public function handle(): void
    {
        $disk = Storage::disk('public');

        try {
            $originalPath = $this->media->storage_key;

            if (! $disk->exists($originalPath)) {
                $this->media->update(['status' => MediaStatus::Failed]);

                return;
            }

            $manager = new ImageManager(new Driver);
            $image = $manager->read($disk->get($originalPath));

            $this->media->update([
                'width' => $image->width(),
                'height' => $image->height(),
                'byte_size' => $disk->size($originalPath),
                'mime_type' => $disk->mimeType($originalPath),
            ]);

            $pathInfo = pathinfo($originalPath);
            $baseName = $pathInfo['filename'];
            $extension = $pathInfo['extension'] ?? 'jpg';
            $directory = $pathInfo['dirname'];

            foreach (self::SIZES as $sizeName => [$width, $height]) {
                $resized = $manager->read($disk->get($originalPath));
                $resized->cover($width, $height);

                $sizedPath = $directory.'/'.$baseName.'_'.$sizeName.'.'.$extension;
                $disk->put($sizedPath, $resized->toJpeg());
            }

            $this->media->update(['status' => MediaStatus::Ready]);
        } catch (\Throwable $e) {
            Log::error('Media processing failed', [
                'media_id' => $this->media->id,
                'error' => $e->getMessage(),
            ]);

            $this->media->update(['status' => MediaStatus::Failed]);
        }
    }
}
