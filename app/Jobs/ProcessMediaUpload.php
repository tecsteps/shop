<?php

namespace App\Jobs;

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Models\ProductMedia;
use GdImage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ProcessMediaUpload implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(public ProductMedia $media) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $disk = Storage::disk('public');

            if (! $disk->exists($this->media->storage_key)) {
                $this->media->forceFill(['status' => MediaStatus::Failed])->save();

                return;
            }

            $path = $disk->path($this->media->storage_key);
            $metadata = [
                'byte_size' => $disk->size($this->media->storage_key),
                'status' => MediaStatus::Ready,
            ];

            if ($this->media->type === MediaType::Image) {
                $size = @getimagesize($path);

                if ($size === false) {
                    throw new RuntimeException('Unable to read media image metadata.');
                }

                $metadata['width'] = $size[0];
                $metadata['height'] = $size[1];
                $metadata['mime_type'] = $size['mime'] ?? $this->media->mime_type;

                $this->generateImageVariants($disk, $path, $metadata['width'], $metadata['height'], $metadata['mime_type']);
            }

            $this->media->forceFill($metadata)->save();
        } catch (Throwable $exception) {
            report($exception);

            if ($this->attempts() < $this->tries) {
                throw $exception;
            }

            $this->media->forceFill(['status' => MediaStatus::Failed])->save();
        }
    }

    private function generateImageVariants(FilesystemAdapter $disk, string $path, int $width, int $height, string $mimeType): void
    {
        $source = $this->createImage($path, $mimeType);
        $extension = $this->extensionForMime($mimeType);

        try {
            foreach (ProductMedia::ImageVariantSizes as $size => $bounds) {
                $resized = $this->resizeImage($source, $width, $height, $bounds['width'], $bounds['height']);

                try {
                    $disk->put(
                        $this->media->imageVariantStorageKey($size, $extension),
                        $this->encodeImage($resized, $mimeType),
                    );

                    if ($extension !== 'webp' && function_exists('imagewebp')) {
                        $disk->put(
                            $this->media->imageVariantStorageKey($size, 'webp'),
                            $this->encodeImage($resized, 'image/webp'),
                        );
                    }
                } finally {
                    imagedestroy($resized);
                }
            }
        } finally {
            imagedestroy($source);
        }
    }

    private function createImage(string $path, string $mimeType): GdImage
    {
        $image = match ($mimeType) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/gif' => imagecreatefromgif($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : false,
            default => false,
        };

        if (! $image instanceof GdImage) {
            throw new RuntimeException('Unsupported image type for media processing: '.$mimeType);
        }

        return $image;
    }

    private function resizeImage(GdImage $source, int $sourceWidth, int $sourceHeight, int $maxWidth, int $maxHeight): GdImage
    {
        $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight, 1);
        $targetWidth = max(1, (int) round($sourceWidth * $ratio));
        $targetHeight = max(1, (int) round($sourceHeight * $ratio));
        $target = imagecreatetruecolor($targetWidth, $targetHeight);

        if (! $target instanceof GdImage) {
            throw new RuntimeException('Unable to create media image variant canvas.');
        }

        imagealphablending($target, false);
        imagesavealpha($target, true);

        if (! imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight)) {
            imagedestroy($target);

            throw new RuntimeException('Unable to resize media image variant.');
        }

        return $target;
    }

    private function encodeImage(GdImage $image, string $mimeType): string
    {
        ob_start();

        $encoded = match ($mimeType) {
            'image/jpeg' => imagejpeg($image, null, 85),
            'image/png' => imagepng($image, null, 6),
            'image/gif' => imagegif($image),
            'image/webp' => imagewebp($image, null, 85),
            default => false,
        };

        $contents = ob_get_clean();

        if (! $encoded || $contents === false || $contents === '') {
            throw new RuntimeException('Unable to encode media image variant.');
        }

        return $contents;
    }

    private function extensionForMime(string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => throw new RuntimeException('Unsupported media MIME type: '.$mimeType),
        };
    }
}
