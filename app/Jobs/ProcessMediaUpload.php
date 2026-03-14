<?php

namespace App\Jobs;

use App\Enums\MediaStatus;
use App\Models\ProductMedia;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessMediaUpload implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        private int $mediaId,
    ) {}

    public function handle(): void
    {
        $media = ProductMedia::find($this->mediaId);

        if (! $media) {
            Log::warning('ProcessMediaUpload: media record not found', ['media_id' => $this->mediaId]);

            return;
        }

        try {
            $this->processImage($media);
            $media->update(['status' => MediaStatus::Ready]);
        } catch (\Throwable $e) {
            Log::error('ProcessMediaUpload: image processing failed', [
                'media_id' => $this->mediaId,
                'error' => $e->getMessage(),
            ]);

            $media->update(['status' => MediaStatus::Failed]);
        }
    }

    private function processImage(ProductMedia $media): void
    {
        if (! function_exists('gd_info')) {
            Log::warning('ProcessMediaUpload: GD extension not available, skipping resize', [
                'media_id' => $media->id,
            ]);

            return;
        }

        $disk = Storage::disk($media->disk ?? 'public');
        $path = $media->path;

        if (! $disk->exists($path)) {
            Log::warning('ProcessMediaUpload: source file not found', [
                'media_id' => $media->id,
                'path' => $path,
            ]);

            return;
        }

        $contents = $disk->get($path);
        $source = @imagecreatefromstring($contents);

        if ($source === false) {
            Log::warning('ProcessMediaUpload: unable to create image from file', [
                'media_id' => $media->id,
            ]);

            return;
        }

        $originalWidth = imagesx($source);
        $originalHeight = imagesy($source);

        $media->update([
            'width' => $originalWidth,
            'height' => $originalHeight,
        ]);

        $sizes = [
            'thumbnail' => ['width' => 150, 'height' => 150, 'crop' => true],
            'medium' => ['width' => 600, 'height' => 600, 'crop' => false],
            'large' => ['width' => 1200, 'height' => 1200, 'crop' => false],
        ];

        $directory = pathinfo($path, PATHINFO_DIRNAME);
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        foreach ($sizes as $sizeName => $dimensions) {
            $resized = $dimensions['crop']
                ? $this->cropToFit($source, $dimensions['width'], $dimensions['height'])
                : $this->fitWithin($source, $dimensions['width'], $dimensions['height'], $originalWidth, $originalHeight);

            if ($resized === null) {
                continue;
            }

            $outputPath = "{$directory}/{$filename}_{$sizeName}.{$extension}";

            ob_start();
            $this->outputImage($resized, $extension);
            $output = ob_get_clean();

            $disk->put($outputPath, $output);
            imagedestroy($resized);
        }

        imagedestroy($source);
    }

    private function cropToFit(\GdImage $source, int $targetWidth, int $targetHeight): \GdImage
    {
        $srcWidth = imagesx($source);
        $srcHeight = imagesy($source);

        $ratio = max($targetWidth / $srcWidth, $targetHeight / $srcHeight);
        $cropWidth = (int) ($targetWidth / $ratio);
        $cropHeight = (int) ($targetHeight / $ratio);
        $srcX = (int) (($srcWidth - $cropWidth) / 2);
        $srcY = (int) (($srcHeight - $cropHeight) / 2);

        $dest = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled($dest, $source, 0, 0, $srcX, $srcY, $targetWidth, $targetHeight, $cropWidth, $cropHeight);

        return $dest;
    }

    private function fitWithin(\GdImage $source, int $maxWidth, int $maxHeight, int $srcWidth, int $srcHeight): ?\GdImage
    {
        if ($srcWidth <= $maxWidth && $srcHeight <= $maxHeight) {
            return null;
        }

        $ratio = min($maxWidth / $srcWidth, $maxHeight / $srcHeight);
        $newWidth = (int) ($srcWidth * $ratio);
        $newHeight = (int) ($srcHeight * $ratio);

        $dest = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($dest, $source, 0, 0, 0, 0, $newWidth, $newHeight, $srcWidth, $srcHeight);

        return $dest;
    }

    private function outputImage(\GdImage $image, string $extension): void
    {
        match (strtolower($extension)) {
            'png' => imagepng($image),
            'gif' => imagegif($image),
            'webp' => imagewebp($image),
            default => imagejpeg($image, null, 85),
        };
    }

    public function failed(\Throwable $exception): void
    {
        $media = ProductMedia::find($this->mediaId);

        if ($media) {
            $media->update(['status' => MediaStatus::Failed]);
        }

        Log::error('ProcessMediaUpload: job failed permanently', [
            'media_id' => $this->mediaId,
            'error' => $exception->getMessage(),
        ]);
    }
}
