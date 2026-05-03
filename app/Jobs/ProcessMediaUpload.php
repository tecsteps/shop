<?php

namespace App\Jobs;

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Models\ProductMedia;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ProcessMediaUpload implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var array<string, array{0: int, 1: int}>
     */
    private const TARGETS = [
        'thumbnail' => [150, 150],
        'small' => [300, 300],
        'medium' => [600, 600],
        'large' => [1200, 1200],
    ];

    public function __construct(
        public int $productMediaId,
        public ?int $storeId = null,
    ) {}

    public function handle(): void
    {
        $media = $this->media();

        try {
            $this->process($media);
        } catch (Throwable $throwable) {
            $media->forceFill([
                'status' => MediaStatus::Failed,
            ])->save();

            Log::error('Media processing failed.', [
                'product_media_id' => $media->getKey(),
                'storage_key' => $media->storage_key,
                'exception' => $throwable->getMessage(),
            ]);

            throw $throwable;
        }
    }

    private function media(): ProductMedia
    {
        $query = ProductMedia::withoutGlobalScopes()->whereKey($this->productMediaId);

        if ($this->storeId !== null) {
            $query->whereHas('product', function (Builder $query): void {
                $query
                    ->withoutGlobalScopes()
                    ->where('store_id', $this->storeId);
            });
        }

        return $query->firstOrFail();
    }

    private function process(ProductMedia $media): void
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($media->storage_key)) {
            throw new RuntimeException('Media file is missing from public storage.');
        }

        $contents = $disk->get($media->storage_key);
        $mimeType = $this->mimeType($contents);
        $byteSize = $disk->size($media->storage_key);

        $dimensions = match ($media->type) {
            MediaType::Image => $this->processImage($media, $contents, $mimeType),
            MediaType::Video => $this->validateVideo($mimeType),
        };

        $media->forceFill([
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],
            'mime_type' => $mimeType,
            'byte_size' => $byteSize,
            'status' => MediaStatus::Ready,
        ])->save();
    }

    /**
     * @return array{width: int, height: int}
     */
    private function processImage(ProductMedia $media, string $contents, string $mimeType): array
    {
        if (! str_starts_with($mimeType, 'image/')) {
            throw new RuntimeException('Media file is not a supported image.');
        }

        $source = @imagecreatefromstring($contents);

        if ($source === false) {
            throw new RuntimeException('Media image could not be decoded.');
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $extension = $this->extensionForMime($mimeType);
        $disk = Storage::disk('public');

        foreach (self::TARGETS as $size => [$maxWidth, $maxHeight]) {
            [$targetWidth, $targetHeight] = $this->fitDimensions($sourceWidth, $sourceHeight, $maxWidth, $maxHeight);
            $resized = imagecreatetruecolor($targetWidth, $targetHeight);

            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagefilledrectangle(
                $resized,
                0,
                0,
                $targetWidth,
                $targetHeight,
                imagecolorallocatealpha($resized, 0, 0, 0, 127),
            );
            imagecopyresampled($resized, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

            $basePath = "media/{$media->product_id}/{$media->getKey()}/{$size}";

            $disk->put("{$basePath}.{$extension}", $this->encodeImage($resized, $extension));

            if (function_exists('imagewebp')) {
                $disk->put("{$basePath}.webp", $this->encodeImage($resized, 'webp'));
            }

            imagedestroy($resized);
        }

        imagedestroy($source);

        return [
            'width' => $sourceWidth,
            'height' => $sourceHeight,
        ];
    }

    /**
     * @return array{width: null, height: null}
     */
    private function validateVideo(string $mimeType): array
    {
        if (! str_starts_with($mimeType, 'video/')) {
            throw new RuntimeException('Media file is not a supported video.');
        }

        return [
            'width' => null,
            'height' => null,
        ];
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function fitDimensions(int $sourceWidth, int $sourceHeight, int $maxWidth, int $maxHeight): array
    {
        $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight, 1);

        return [
            max(1, (int) round($sourceWidth * $ratio)),
            max(1, (int) round($sourceHeight * $ratio)),
        ];
    }

    private function encodeImage(\GdImage $image, string $extension): string
    {
        ob_start();

        $encoded = match ($extension) {
            'jpg' => imagejpeg($image, null, 90),
            'png' => imagepng($image),
            'gif' => imagegif($image),
            'webp' => imagewebp($image, null, 90),
            default => false,
        };

        $contents = ob_get_clean();

        if ($encoded === false || $contents === false) {
            throw new RuntimeException('Unable to encode resized media image.');
        }

        return $contents;
    }

    private function mimeType(string $contents): string
    {
        $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents);

        if (! is_string($mimeType) || $mimeType === '') {
            throw new RuntimeException('Unable to determine media MIME type.');
        }

        return $mimeType;
    }

    private function extensionForMime(string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => throw new RuntimeException("Unsupported image MIME type [{$mimeType}]."),
        };
    }
}
