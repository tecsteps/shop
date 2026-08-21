<?php

namespace App\Jobs;

use App\Models\ProductMedia;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ProcessMediaUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    public function __construct(public ProductMedia $media) {}

    public function handle(): void
    {
        $media = $this->media->fresh();
        $disk = Storage::disk('public');
        $sourceKey = $media?->storage_key ?: $media?->path;

        if ($media === null || $sourceKey === null || ! $disk->exists($sourceKey)) {
            throw new RuntimeException('The uploaded media file is not available yet.');
        }

        $contents = $disk->get($sourceKey);
        $mimeType = $media->mime_type ?: $disk->mimeType($sourceKey) ?: 'application/octet-stream';
        $metadata = $media->metadata ?? [];
        $width = null;
        $height = null;
        $variants = ['original' => $sourceKey];

        if (str_starts_with($mimeType, 'image/')) {
            $dimensions = @getimagesizefromstring($contents);
            if ($dimensions === false) {
                throw new RuntimeException('The uploaded image could not be decoded.');
            }
            [$width, $height] = $dimensions;
            $variants = $this->writeImageVariants($disk, $sourceKey, $contents, $mimeType, $width, $height);
        }

        $metadata['variants'] = $variants;
        $metadata['processed_at'] = now()->toIso8601String();
        $media->update([
            'status' => 'ready',
            'width' => $width,
            'height' => $height,
            'mime_type' => $mimeType,
            'byte_size' => strlen($contents),
            'checksum' => hash('sha256', $contents),
            'url' => $disk->url($sourceKey),
            'metadata' => $metadata,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        $media = $this->media->fresh();
        $metadata = $media?->metadata ?? [];
        $metadata['error'] = $exception->getMessage();
        $metadata['failed_at'] = now()->toIso8601String();
        $media?->update(['status' => 'failed', 'metadata' => $metadata]);
    }

    /**
     * @return array<string, string>
     */
    private function writeImageVariants(object $disk, string $sourceKey, string $contents, string $mimeType, int $width, int $height): array
    {
        $variants = ['original' => $sourceKey];
        $directory = trim(pathinfo($sourceKey, PATHINFO_DIRNAME), '.');
        $extension = strtolower(pathinfo($sourceKey, PATHINFO_EXTENSION));

        foreach (['thumbnail' => 320, 'medium' => 800, 'large' => 1600] as $name => $maximum) {
            if ($width <= $maximum && $height <= $maximum) {
                $variants[$name] = $sourceKey;

                continue;
            }

            $resized = $this->resize($contents, $mimeType, $width, $height, $maximum);
            if ($resized === null) {
                $variants[$name] = $sourceKey;

                continue;
            }

            $key = $directory.'/'.$name.'.'.$extension;
            $disk->put($key, $resized);
            $variants[$name] = $key;
        }

        return $variants;
    }

    private function resize(string $contents, string $mimeType, int $width, int $height, int $maximum): ?string
    {
        $source = @imagecreatefromstring($contents);
        if ($source === false) {
            return null;
        }

        $scale = min($maximum / $width, $maximum / $height);
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));
        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        ob_start();

        $written = match ($mimeType) {
            'image/png' => imagepng($target, null, 8),
            'image/webp' => function_exists('imagewebp') ? imagewebp($target, null, 85) : false,
            default => imagejpeg($target, null, 85),
        };

        $result = $written ? ob_get_clean() : false;
        imagedestroy($source);
        imagedestroy($target);

        return is_string($result) ? $result : null;
    }
}
