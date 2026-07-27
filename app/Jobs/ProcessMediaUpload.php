<?php

namespace App\Jobs;

use App\Enums\MediaStatus;
use App\Models\ProductMedia;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ProcessMediaUpload implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Resize targets: key => maximum width/height in pixels.
     *
     * @var array<string, int>
     */
    private const TARGETS = [
        'thumbnail' => 150,
        'small' => 300,
        'medium' => 600,
        'large' => 1200,
    ];

    public function __construct(public ProductMedia $media) {}

    /**
     * Resize the original into the standard sizes and mark the record ready.
     *
     * @throws RuntimeException when the original cannot be processed
     */
    public function handle(): void
    {
        if (! extension_loaded('gd')) {
            throw new RuntimeException('The GD extension is required to process media uploads.');
        }

        $disk = Storage::disk('public');
        $originalPath = $disk->path($this->media->storage_key);

        $info = @getimagesize($originalPath);

        if ($info === false) {
            throw new RuntimeException("Cannot read image data for media {$this->media->id}.");
        }

        [$width, $height] = $info;
        $mimeType = $info['mime'];

        $source = $this->createImageFrom($originalPath, $mimeType);

        foreach (self::TARGETS as $size => $maxDimension) {
            [$targetWidth, $targetHeight] = $this->containDimensions($width, $height, $maxDimension);

            $resized = imagecreatetruecolor($targetWidth, $targetHeight);

            if ($resized === false) {
                throw new RuntimeException("Failed to allocate canvas for media {$this->media->id}.");
            }

            imagecopyresampled($resized, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

            $relativePath = $this->media->pathFor($size);
            $absolutePath = $disk->path($relativePath);

            if (! is_dir(dirname($absolutePath))) {
                mkdir(dirname($absolutePath), 0755, true);
            }

            $this->saveImage($resized, $absolutePath, $mimeType);
            imagedestroy($resized);
        }

        imagedestroy($source);

        $this->media->update([
            'width' => $width,
            'height' => $height,
            'mime_type' => $mimeType,
            'byte_size' => $disk->size($this->media->storage_key),
            'status' => MediaStatus::Ready,
        ]);
    }

    /**
     * Mark the media as failed after the job exhausted its attempts.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error('Media processing failed', [
            'product_media_id' => $this->media->id,
            'storage_key' => $this->media->storage_key,
            'error' => $exception?->getMessage(),
        ]);

        $this->media->update(['status' => MediaStatus::Failed]);
    }

    /**
     * Load an image resource from disk based on its mime type.
     */
    private function createImageFrom(string $path, string $mimeType): \GdImage
    {
        $image = match ($mimeType) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            'image/gif' => @imagecreatefromgif($path),
            default => false,
        };

        if ($image === false) {
            throw new RuntimeException("Unsupported or corrupt image ({$mimeType}) for media {$this->media->id}.");
        }

        return $image;
    }

    /**
     * Compute dimensions contained within the max size, preserving aspect
     * ratio and never upscaling.
     *
     * @return array{0: int, 1: int}
     */
    private function containDimensions(int $width, int $height, int $maxDimension): array
    {
        $ratio = min($maxDimension / $width, $maxDimension / $height, 1.0);

        return [
            max(1, (int) round($width * $ratio)),
            max(1, (int) round($height * $ratio)),
        ];
    }

    /**
     * Persist an image resource in the given format.
     */
    private function saveImage(\GdImage $image, string $path, string $mimeType): void
    {
        $saved = match ($mimeType) {
            'image/jpeg' => imagejpeg($image, $path, 85),
            'image/png' => imagepng($image, $path),
            'image/webp' => imagewebp($image, $path),
            'image/gif' => imagegif($image, $path),
            default => false,
        };

        if (! $saved) {
            throw new RuntimeException("Failed to write resized image to {$path}.");
        }
    }
}
