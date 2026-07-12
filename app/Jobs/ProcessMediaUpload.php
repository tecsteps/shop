<?php

namespace App\Jobs;

use App\Models\ProductMedia;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class ProcessMediaUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly ProductMedia $media) {}

    public function handle(): void
    {
        $media = $this->media->fresh();
        if ($media === null) {
            return;
        }

        try {
            $disk = Storage::disk('public');
            if (! $disk->exists($media->storage_key)) {
                throw new \RuntimeException('The source media file is missing.');
            }

            $path = $disk->path($media->storage_key);
            $info = @getimagesize($path);
            if ($info === false) {
                throw new \RuntimeException('The uploaded file is not a supported image.');
            }

            [$width, $height] = $info;
            $media->update([
                'width' => $width,
                'height' => $height,
                'mime_type' => $info['mime'] ?? $disk->mimeType($media->storage_key),
                'byte_size' => $disk->size($media->storage_key),
            ]);

            $contents = $disk->get($media->storage_key);
            $source = function_exists('imagecreatefromstring') ? @imagecreatefromstring($contents) : false;
            if ($source === false) {
                throw new \RuntimeException('This server cannot decode the uploaded image.');
            }

            $extension = $this->extensionForMime((string) ($info['mime'] ?? ''));
            foreach (['thumbnail' => 150, 'small' => 300, 'medium' => 600, 'large' => 1200] as $name => $maximum) {
                $scale = min(1, $maximum / max($width, $height));
                $targetWidth = max(1, (int) round($width * $scale));
                $targetHeight = max(1, (int) round($height * $scale));
                $target = imagecreatetruecolor($targetWidth, $targetHeight);
                imagealphablending($target, false);
                imagesavealpha($target, true);
                imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

                $directory = "media/{$media->product_id}/{$media->id}";
                $disk->put("{$directory}/{$name}.{$extension}", $this->encode($target, $extension));
                if ($extension !== 'webp' && function_exists('imagewebp')) {
                    $disk->put("{$directory}/{$name}.webp", $this->encode($target, 'webp'));
                }
                imagedestroy($target);
            }
            imagedestroy($source);

            $media->update(['status' => 'ready']);
        } catch (Throwable $exception) {
            $media->update(['status' => 'failed']);
            Log::error('Product media processing failed.', ['media_id' => $media->id, 'error' => $exception->getMessage()]);
            throw $exception;
        }
    }

    private function extensionForMime(string $mime): string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/avif' => 'avif',
            default => throw new \RuntimeException('The uploaded image format is not supported.'),
        };
    }

    /** @param \GdImage|resource $image */
    private function encode(mixed $image, string $extension): string
    {
        ob_start();
        $success = match ($extension) {
            'jpg' => imagejpeg($image, null, 88),
            'png' => imagepng($image, null, 7),
            'gif' => imagegif($image),
            'webp' => imagewebp($image, null, 85),
            'avif' => function_exists('imageavif') && imageavif($image, null, 70),
            default => false,
        };
        $contents = (string) ob_get_clean();
        if (! $success || $contents === '') {
            throw new \RuntimeException('The resized image could not be encoded.');
        }

        return $contents;
    }
}
