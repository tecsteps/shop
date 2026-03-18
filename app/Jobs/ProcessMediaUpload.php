<?php

namespace App\Jobs;

use App\Enums\MediaStatus;
use App\Models\ProductMedia;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
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
        public ProductMedia $media,
    ) {}

    public function handle(): void
    {
        try {
            $disk = Storage::disk('public');
            $path = $this->media->storage_key;

            if (! $disk->exists($path)) {
                $this->media->update(['status' => MediaStatus::Failed]);

                return;
            }

            $contents = $disk->get($path);
            $manager = new ImageManager(new \Intervention\Image\Drivers\Gd\Driver);

            $pathInfo = pathinfo($path);
            $baseName = $pathInfo['filename'];
            $extension = $pathInfo['extension'] ?? 'jpg';
            $directory = $pathInfo['dirname'];

            foreach (self::SIZES as $sizeName => $dimensions) {
                $image = $manager->read($contents);
                $image->cover($dimensions['width'], $dimensions['height']);

                $sizePath = $directory.'/'.$baseName.'-'.$sizeName.'.'.$extension;
                $disk->put($sizePath, $image->toJpeg());
            }

            $originalImage = $manager->read($contents);
            $this->media->update([
                'status' => MediaStatus::Ready,
                'width' => $originalImage->width(),
                'height' => $originalImage->height(),
            ]);
        } catch (\Throwable) {
            $this->media->update(['status' => MediaStatus::Failed]);
        }
    }
}
